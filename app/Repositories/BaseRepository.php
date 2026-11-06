<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\{DB, Tenant, TenantException};

/**
 * كل استعلام على جدول tenant يمر من هنا ويُقيَّد بـ school_id تلقائيًا (من Tenant فقط).
 * محاولة استخدام جدول tenant بلا سياق = استثناء صريح.
 */
abstract class BaseRepository
{
    protected string $table;
    protected bool $tenantScoped = true;
    protected bool $softDelete = false;
    /** الأعمدة المسموح كتابتها (حماية Mass Assignment). */
    protected array $fillable = [];

    protected function tenantId(): int { return Tenant::require(); }

    protected function scopeSql(string $alias = ''): string
    {
        $p = $alias !== '' ? "$alias." : '';
        $sql = '';
        if ($this->tenantScoped) { $this->tenantId(); $sql .= "{$p}school_id = :__tenant"; }
        if ($this->softDelete) { $sql .= ($sql ? ' AND ' : '') . "{$p}deleted_at IS NULL"; }
        return $sql ?: '1=1';
    }
    protected function bind(array $params): array
    {
        if ($this->tenantScoped) { $params['__tenant'] = $this->tenantId(); }
        return $params;
    }

    public function find(int $id): ?array
    {
        return DB::one("SELECT * FROM {$this->table} WHERE id = :id AND " . $this->scopeSql(), $this->bind(['id' => $id]));
    }
    public function findOrFail(int $id): array
    {
        return $this->find($id) ?? throw new \App\Core\HttpException(404, 'السجل غير موجود');
    }

    /** where: عمود=>قيمة (مساواة فقط). */
    public function where(array $where = [], string $order = 'id DESC', ?int $limit = null): array
    {
        [$cond, $params] = $this->cond($where);
        $sql = "SELECT * FROM {$this->table} WHERE " . $this->scopeSql() . $cond . " ORDER BY $order";
        if ($limit) { $sql .= ' LIMIT ' . (int)$limit; }
        return DB::all($sql, $this->bind($params));
    }
    public function count(array $where = []): int
    {
        [$cond, $params] = $this->cond($where);
        return (int)DB::value("SELECT COUNT(*) FROM {$this->table} WHERE " . $this->scopeSql() . $cond, $this->bind($params));
    }
    /** ترقيم من الخادم. $search: ['col'=>'LIKE value'] مجموعة OR. */
    public function paginate(int $page, int $perPage, array $where = [], array $searchCols = [], string $q = '', string $order = 'id DESC'): array
    {
        $page = max(1, $page); $perPage = min(100, max(1, $perPage));
        [$cond, $params] = $this->cond($where);
        if ($q !== '' && $searchCols) {
            $parts = [];
            foreach ($searchCols as $i => $c) { $parts[] = "$c LIKE :__q$i"; $params["__q$i"] = '%' . addcslashes($q, '%_\\') . '%'; }
            $cond .= ' AND (' . implode(' OR ', $parts) . ')';
        }
        $base = "FROM {$this->table} WHERE " . $this->scopeSql() . $cond;
        $total = (int)DB::value("SELECT COUNT(*) $base", $this->bind($params));
        $rows = DB::all("SELECT * $base ORDER BY $order LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $this->bind($params));
        return ['rows' => $rows, 'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => (int)ceil($total / $perPage)]];
    }

    public function insert(array $data): int
    {
        $data = array_intersect_key($data, array_flip($this->fillable));
        if ($this->tenantScoped) { $data['school_id'] = $this->tenantId(); }
        $cols = array_keys($data);
        DB::query("INSERT INTO {$this->table} (" . implode(',', $cols) . ') VALUES (' . implode(',', array_map(fn($c) => ":$c", $cols)) . ')', $data);
        return DB::lastId();
    }
    public function update(int $id, array $data): bool
    {
        $data = array_intersect_key($data, array_flip($this->fillable));
        if (!$data) { return false; }
        $set = implode(',', array_map(fn($c) => "$c = :v_$c", array_keys($data)));
        $params = ['id' => $id];
        foreach ($data as $k => $v) { $params["v_$k"] = $v; }
        return DB::query("UPDATE {$this->table} SET $set WHERE id = :id AND " . $this->scopeSql(), $this->bind($params))->rowCount() > 0;
    }
    public function delete(int $id): bool
    {
        if ($this->softDelete) {
            return DB::query("UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id AND " . $this->scopeSql(), $this->bind(['id' => $id]))->rowCount() > 0;
        }
        return DB::query("DELETE FROM {$this->table} WHERE id = :id AND " . $this->scopeSql(), $this->bind(['id' => $id]))->rowCount() > 0;
    }

    private function cond(array $where): array
    {
        $cond = ''; $params = [];
        foreach ($where as $c => $v) {
            if (!preg_match('/^[a-z_]+$/', $c)) { throw new \InvalidArgumentException('اسم عمود غير صالح'); }
            if ($v === null) { $cond .= " AND $c IS NULL"; continue; }
            $cond .= " AND $c = :w_$c"; $params["w_$c"] = $v;
        }
        return [$cond, $params];
    }
}
