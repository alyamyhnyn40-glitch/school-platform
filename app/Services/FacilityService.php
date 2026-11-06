<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** المرافق (قاعات، مختبرات، ملاعب…) وحجزها بمنع التعارض. */
final class FacilityService
{
    public static function facilities(): array
    {
        Auth::authorize('facilities.book'); return DB::all('SELECT * FROM facilities WHERE school_id = ? ORDER BY name', [Tenant::require()]);
    }
    public static function save(?int $id, string $name, ?string $kind, ?string $capacity, string $status): int
    {
        Auth::authorize('facilities.manage'); $sid = Tenant::require();
        if (trim($name) === '') { throw new HttpException(422, 'اسم المرفق مطلوب', ['name' => 'مطلوب']); } if (!in_array($status, ['available', 'maintenance'], true)) { throw new HttpException(422, 'حالة غير صالحة'); }
        $cap = ($capacity === null || $capacity === '') ? null : (int)$capacity; if ($cap !== null && $cap < 1) { throw new HttpException(422, 'السعة غير صالحة', ['capacity' => 'غير صالحة']); }
        if ($id) { DB::value('SELECT 1 FROM facilities WHERE id = ? AND school_id = ?', [$id, $sid]) ?? throw new HttpException(404, 'المرفق غير موجود'); DB::query('UPDATE facilities SET name=?, kind=?, capacity=?, status=? WHERE id=?', [trim($name), $kind ?: null, $cap, $status, $id]); }
        else { DB::query('INSERT INTO facilities (school_id, name, kind, capacity, status) VALUES (?,?,?,?,?)', [$sid, trim($name), $kind ?: null, $cap, $status]); $id = DB::lastId(); }
        AuditService::log('facility.save', 'facility', $id, null, ['name' => $name, 'status' => $status]); return $id;
    }
    public static function delete(int $id): void
    {
        Auth::authorize('facilities.manage'); if (DB::value('SELECT 1 FROM facility_bookings WHERE facility_id = ? AND school_id = ? AND ends_at > NOW() AND cancelled_at IS NULL LIMIT 1', [$id, Tenant::require()])) { throw new HttpException(409, 'توجد حجوزات قادمة على هذا المرفق'); }
        DB::query('DELETE FROM facility_bookings WHERE facility_id = ? AND school_id = ?', [$id, Tenant::require()]); DB::query('DELETE FROM facilities WHERE id = ? AND school_id = ?', [$id, Tenant::require()]);
    }
    public static function book(int $facilityId, string $startsAt, string $endsAt, ?string $purpose): int
    {
        Auth::authorize('facilities.book'); $sid = Tenant::require();
        $s = strtotime(str_replace('T', ' ', $startsAt)); $e = strtotime(str_replace('T', ' ', $endsAt)); if (!$s || !$e) { throw new HttpException(422, 'وقت غير صالح'); }
        if ($e <= $s) { throw new HttpException(422, 'وقت الانتهاء يجب أن يلي البدء', ['ends_at' => 'غير صالح']); } if ($e - $s > 12 * 3600) { throw new HttpException(422, 'أقصى مدة حجز 12 ساعة'); } if ($s < time() - 300) { throw new HttpException(422, 'لا يمكن الحجز في وقت مضى', ['starts_at' => 'غير صالح']); }
        return DB::transaction(function () use ($facilityId, $s, $e, $purpose, $sid) {
            $f = DB::one('SELECT * FROM facilities WHERE id = ? AND school_id = ? FOR UPDATE', [$facilityId, $sid]) ?? throw new HttpException(404, 'المرفق غير موجود');
            if ($f['status'] !== 'available') { throw new HttpException(422, 'المرفق تحت الصيانة'); }
            $c = DB::one('SELECT b.starts_at, b.ends_at, u.full_name FROM facility_bookings b JOIN users u ON u.id = b.booked_by WHERE b.facility_id = ? AND b.school_id = ? AND b.cancelled_at IS NULL AND b.starts_at < ? AND b.ends_at > ? LIMIT 1', [$facilityId, $sid, date('Y-m-d H:i:s', $e), date('Y-m-d H:i:s', $s)]);
            if ($c) { throw new HttpException(409, "محجوز من {$c['full_name']} (" . substr($c['starts_at'], 11, 5) . '–' . substr($c['ends_at'], 11, 5) . ') في هذا الوقت'); }
            DB::query('INSERT INTO facility_bookings (school_id, facility_id, booked_by, starts_at, ends_at, purpose) VALUES (?,?,?,?,?,?)', [$sid, $facilityId, Auth::id(), date('Y-m-d H:i:s', $s), date('Y-m-d H:i:s', $e), $purpose ? mb_substr($purpose, 0, 200) : null]); $id = DB::lastId();
            AuditService::log('facility.book', 'facility_booking', $id, null, ['facility' => $f['name'], 'from' => date('Y-m-d H:i', $s)]); return $id;
        });
    }
    public static function cancel(int $bookingId): void
    {
        Auth::authorize('facilities.book'); $b = DB::one('SELECT * FROM facility_bookings WHERE id = ? AND school_id = ?', [$bookingId, Tenant::require()]) ?? throw new HttpException(404, 'الحجز غير موجود');
        if ((int)$b['booked_by'] !== Auth::id() && !Auth::can('facilities.manage')) { throw new HttpException(403, 'يمكنك إلغاء حجوزاتك فقط'); }
        if ($b['cancelled_at']) { return; } DB::query('UPDATE facility_bookings SET cancelled_at = ? WHERE id = ?', [now(), $bookingId]); AuditService::log('facility.cancel', 'facility_booking', $bookingId);
    }
    /** حجوزات يوم (أو فترة) لمرفق أو للجميع. */
    public static function bookings(?int $facilityId, string $from, string $to): array
    {
        Auth::authorize('facilities.book'); $w = 'b.school_id = ? AND b.cancelled_at IS NULL AND b.starts_at < ? AND b.ends_at > ?'; $p = [Tenant::require(), $to . ' 23:59:59', $from . ' 00:00:00'];
        if ($facilityId) { $w .= ' AND b.facility_id = ?'; $p[] = $facilityId; }
        return DB::all("SELECT b.*, f.name AS facility, u.full_name AS booker FROM facility_bookings b JOIN facilities f ON f.id = b.facility_id JOIN users u ON u.id = b.booked_by WHERE $w ORDER BY b.starts_at", $p);
    }
}
