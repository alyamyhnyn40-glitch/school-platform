<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\HttpException;

/** رفع صور آمن: فحص النوع الحقيقي بمحتوى الملف، حدّ الحجم والأبعاد، وإعادة ترميز عبر GD (تُسقط أي بيانات وصفية أو حمولة مخفية). PNG/JPEG/WebP فقط (لا SVG). */
final class ImageUpload
{
    public const MIMES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    /** @return string اسم الملف المخزَّن (32 hex + امتداد) داخل $dir */
    public static function storeSafe(array $f, string $dir, int $maxBytes, int $maxDim): string
    {
        $tmp = (string)($f['tmp_name'] ?? ''); $uploaded = $tmp !== '' && (is_uploaded_file($tmp) || (PHP_SAPI === 'cli' && is_file($tmp)));   // CLI = الاختبارات فقط
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !$uploaded) { throw new HttpException(422, 'تعذّر رفع الصورة', ['logo' => 'فشل الرفع']); }
        if (($f['size'] ?? 0) > $maxBytes || filesize($f['tmp_name']) > $maxBytes) { throw new HttpException(422, 'حجم الصورة يتجاوز ' . round($maxBytes / 1048576, 1) . ' ميغابايت', ['logo' => 'كبيرة']); }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(self::MIMES[$mime])) { throw new HttpException(422, 'الصيغ المسموحة: PNG وJPG وWebP', ['logo' => 'صيغة غير مسموحة']); }
        $info = @getimagesize($f['tmp_name']); if (!$info || $info[0] < 16 || $info[1] < 16 || $info[0] * $info[1] > 40_000_000) { throw new HttpException(422, 'صورة غير صالحة', ['logo' => 'غير صالحة']); }
        $src = match ($mime) { 'image/png' => @imagecreatefrompng($f['tmp_name']), 'image/jpeg' => @imagecreatefromjpeg($f['tmp_name']), default => @imagecreatefromwebp($f['tmp_name']) };
        if (!$src) { throw new HttpException(422, 'تعذّرت قراءة الصورة', ['logo' => 'غير صالحة']); }
        [$w, $h] = [imagesx($src), imagesy($src)]; $sc = min(1, $maxDim / max($w, $h)); $nw = max(1, (int)round($w * $sc)); $nh = max(1, (int)round($h * $sc));
        $dst = imagecreatetruecolor($nw, $nh); imagealphablending($dst, false); imagesavealpha($dst, true); imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) { throw new HttpException(500, 'تعذّر إنشاء مجلد الحفظ'); }
        $ext = self::MIMES[$mime]; $name = bin2hex(random_bytes(16)) . '.' . $ext; $path = rtrim($dir, '/') . '/' . $name;
        $ok = match ($ext) { 'png' => imagepng($dst, $path, 6), 'jpg' => imagejpeg($dst, $path, 88), default => imagewebp($dst, $path, 88) };
        if (!$ok) { throw new HttpException(500, 'تعذّر حفظ الصورة'); }
        return $name;
    }
}
