<?php
declare(strict_types=1);

use App\Core\{Csrf, Lang, Session, View, Env};

function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function __t(string $key, array $vars = []): string { return Lang::get($key, $vars); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">'; }
function csrf_token(): string { return Csrf::token(); }
function base_path(string $p = ''): string { return dirname(__DIR__) . ($p !== '' ? '/' . ltrim($p, '/') : ''); }
function storage_path(string $p = ''): string { return base_path('storage' . ($p !== '' ? '/' . ltrim($p, '/') : '')); }
function url(string $path = ''): string { return rtrim((string)Env::get('APP_BASE_PATH', ''), '/') . '/' . ltrim($path, '/'); }
function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
function old(string $k, string $d = ''): string { return e(Session::getFlash('old')[$k] ?? $_POST[$k] ?? $d); }
function can(string $perm): bool { return \App\Core\Auth::can($perm); }
function now(): string { return date('Y-m-d H:i:s'); }

/** وسم صورة سؤال/خيار (ملف محمي يمرّ عبر /files/{id} بفحص الصلاحية). */
function qimg(?int $fileId, string $cls = 'q-img', string $alt = 'صورة'): string
{
    return $fileId ? '<img class="' . e($cls) . '" loading="lazy" src="' . e(url('files/' . $fileId)) . '" alt="' . e($alt) . '">' : '';
}

/** نص الخيار (بمعادلاته) وصورته إن وُجدت — HTML آمن. */
function qopt(array $q, mixed $idx): string
{
    if ($idx === null || $idx === '' || !isset($q['options'][$idx])) { return ''; }
    $img = !empty($q['option_images'][$idx]) ? qimg((int)$q['option_images'][$idx], 'q-opt-img') : '';
    return '<span class="math-content">' . e($q['options'][$idx]) . '</span>' . $img;
}

if (!function_exists('options')) {
    /** خيارات <option> من مصفوفة قيم (القيمة = النص) مع تحديد القيمة الحالية. القيمة المخزَّنة غير الموجودة بالقائمة تُضاف حتى لا تضيع. */
    function options(array $values, ?string $current = null, bool $blank = true, string $blankLabel = '— اختر —'): string
    {
        $h = $blank ? '<option value="">' . htmlspecialchars($blankLabel, ENT_QUOTES, 'UTF-8') . '</option>' : '';
        if ($current !== null && $current !== '' && !in_array($current, $values, true)) { $values[] = $current; }
        foreach ($values as $v) { $h .= '<option value="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"' . ((string)$v === (string)$current ? ' selected' : '') . '>' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '</option>'; }
        return $h;
    }
    /** حقل محافظة + مدينة مترابطان (المدينة تتصفى بحسب المحافظة وتقبل كتابة قيمة جديدة). */
    function geo_fields(?string $gov = null, ?string $city = null, string $cls = 'col-md-6'): string
    {
        $id = 'g' . bin2hex(random_bytes(3));
        return '<div class="' . $cls . '"><label class="form-label" for="' . $id . 'g">المحافظة</label><select class="form-select" id="' . $id . 'g" name="governorate" data-geo-gov="' . $id . '">' . options(\App\Core\Lookup::governorates(), $gov) . '</select></div>'
            . '<div class="' . $cls . '"><label class="form-label" for="' . $id . 'c">المدينة / المديرية</label><input class="form-control" id="' . $id . 'c" name="city" list="' . $id . 'l" data-geo-city="' . $id . '" autocomplete="off" value="' . htmlspecialchars((string)$city, ENT_QUOTES, 'UTF-8') . '" placeholder="اختر أو اكتب"><datalist id="' . $id . 'l"></datalist></div>';
    }
}

if (!function_exists('app_version')) {
    function app_version(): string { $f = base_path('VERSION'); return is_file($f) ? trim((string)file_get_contents($f)) : '0.0.0'; }
}
