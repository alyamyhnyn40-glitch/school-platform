<?php
declare(strict_types=1);
namespace App\Repositories;

final class AcademicYearRepository extends BaseRepository
{
    protected string $table = 'academic_years';
    protected array $fillable = ['name','starts_on','ends_on','status','closed_at'];

    public function active(): ?array { return $this->where(['status' => 'active'], 'id DESC', 1)[0] ?? null; }
}
