<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الشهادات الفصلية: تُجمَّد في snapshot عند الإصدار فلا تتغير بتغير الدرجات لاحقًا. */
final class ReportCardService
{
    /** يصدر شهادات الفصل لكل طلاب الشعبة. $allowIncomplete: السماح بإصدار نتائج غير مكتملة/غير معتمدة (يُسجَّل). */
    public static function issueForSection(int $termId, int $sectionId, bool $allowIncomplete = false): array
    {
        Auth::authorize('reportcards.issue');
        $res = ResultsService::termResults($termId, $sectionId);
        $sid = Tenant::require();
        $blocked = array_filter($res['students'], fn($s) => !$s['complete'] || !$s['all_approved']);
        if ($blocked && !$allowIncomplete) { throw new HttpException(422, count($blocked) . ' طالب نتائجهم غير مكتملة أو غير معتمدة. اعتمد الدرجات أو اسمح بالإصدار الجزئي.'); }
        $school = DB::one('SELECT name, city, phone FROM schools WHERE id = ?', [$sid]);
        $year = DB::one('SELECT name FROM academic_years WHERE id = ?', [$res['term']['academic_year_id']]);
        $n = 0;
        DB::transaction(function () use ($res, $sid, $school, $year, $termId, &$n) {
            foreach ($res['students'] as $st) {
                if ($st['average'] === null) { continue; }
                $att = DB::one("SELECT SUM(r.status = 'absent') AS absent, SUM(r.status = 'late') AS late, SUM(r.status = 'excused') AS excused FROM attendance_records r JOIN attendance_sessions a ON a.id = r.session_id
                    WHERE r.enrollment_id = ? AND r.school_id = ? AND a.session_date BETWEEN ? AND ?", [$st['enrollment_id'], $sid, $res['term']['starts_on'], $res['term']['ends_on']]);
                $subs = [];
                foreach ($res['subjects'] as $sub) { $x = $st['subjects'][$sub['id']]; if ($x['has_assessments']) { $subs[] = ['name' => $sub['name'], 'total' => $x['total'], 'percent' => $x['percent'], 'letter' => $x['letter'], 'passed' => $x['passed']]; } }
                $snap = ['school' => $school, 'year' => $year['name'], 'term' => $res['term']['name'], 'is_final' => (bool)$res['term']['is_final'], 'section' => $res['section']['grade_name'] . ' / ' . $res['section']['name'],
                    'student' => ['name' => $st['full_name'], 'no' => $st['student_no']], 'scheme' => $res['scheme'], 'subjects' => $subs, 'average' => $st['average'], 'letter' => $st['letter'], 'rank' => $st['rank'], 'class_size' => count($res['students']),
                    'result' => $st['result'], 'attendance' => ['absent' => (int)($att['absent'] ?? 0), 'late' => (int)($att['late'] ?? 0), 'excused' => (int)($att['excused'] ?? 0)], 'incomplete' => !$st['complete'] || !$st['all_approved'], 'issued_at' => date('Y-m-d')];
                $json = json_encode($snap, JSON_UNESCAPED_UNICODE);
                $old = DB::one("SELECT id FROM report_cards WHERE enrollment_id = ? AND term_id = ? AND kind = 'term'", [$st['enrollment_id'], $termId]);
                if ($old) { DB::query('UPDATE report_cards SET snapshot = ?, average = ?, rank_in_section = ?, result = ?, issued_at = NOW(), issued_by = ? WHERE id = ?', [$json, $st['average'], $st['rank'], $st['result'], Auth::id(), $old['id']]); }
                else { DB::query("INSERT INTO report_cards (school_id, enrollment_id, term_id, kind, snapshot, average, rank_in_section, result, issued_by) VALUES (?,?,?, 'term', ?,?,?,?,?)", [$sid, $st['enrollment_id'], $termId, $json, $st['average'], $st['rank'], $st['result'], Auth::id()]); }
                $n++;
            }
        });
        AuditService::log('reportcard.issue', 'section', $sectionId, null, ['term' => $termId, 'count' => $n, 'partial' => $allowIncomplete && (bool)$blocked]);
        return ['issued' => $n, 'incomplete' => count($blocked)];
    }

    /** الشهادة النهائية للعام (تجميع الفصول). تشترط قفل كل الفصول أو إصدارًا جزئيًا صريحًا. */
    public static function issueFinalForSection(int $yearId, int $sectionId, bool $allowIncomplete = false): array
    {
        Auth::authorize('reportcards.issue');
        $res = ResultsService::yearResults($yearId, $sectionId);
        $sid = Tenant::require();
        $unlocked = (int)DB::value('SELECT COUNT(*) FROM terms WHERE academic_year_id = ? AND school_id = ? AND grades_locked = 0', [$yearId, $sid]);
        $blocked = array_filter($res['students'], fn($s) => !$s['complete'] || !$s['all_approved']);
        if (($unlocked || $blocked) && !$allowIncomplete) { throw new HttpException(422, ($unlocked ? "يوجد $unlocked فصل غير مقفل. " : '') . ($blocked ? count($blocked) . ' طالب نتائجهم غير مكتملة. ' : '') . 'اقفل الفصول أو اسمح بالإصدار الجزئي.'); }
        $school = DB::one('SELECT name, city, phone FROM schools WHERE id = ?', [$sid]); $year = DB::one('SELECT name, starts_on, ends_on FROM academic_years WHERE id = ?', [$yearId]); $n = 0;
        DB::transaction(function () use ($res, $sid, $school, $year, $unlocked, &$n) {
            foreach ($res['students'] as $st) {
                if ($st['average'] === null) { continue; }
                $att = DB::one("SELECT SUM(r.status = 'absent') AS absent, SUM(r.status = 'late') AS late, SUM(r.status = 'excused') AS excused FROM attendance_records r JOIN attendance_sessions a ON a.id = r.session_id WHERE r.enrollment_id = ? AND r.school_id = ? AND a.session_date BETWEEN ? AND ?", [$st['enrollment_id'], $sid, $year['starts_on'], $year['ends_on']]);
                $subs = []; foreach ($res['subjects'] as $sub) { $x = $st['subjects'][$sub['id']]; if ($x['has_assessments']) { $subs[] = ['name' => $sub['name'], 'total' => $x['total'], 'percent' => $x['percent'], 'letter' => $x['letter'], 'passed' => $x['passed']]; } }
                $snap = ['school' => $school, 'year' => $year['name'], 'term' => 'النتيجة النهائية للعام', 'is_final' => true, 'section' => $res['section']['grade_name'] . ' / ' . $res['section']['name'], 'student' => ['name' => $st['full_name'], 'no' => $st['student_no']],
                    'scheme' => $res['scheme'], 'subjects' => $subs, 'average' => $st['average'], 'letter' => $st['letter'], 'rank' => $st['rank'], 'class_size' => count($res['students']), 'result' => $st['result'],
                    'attendance' => ['absent' => (int)($att['absent'] ?? 0), 'late' => (int)($att['late'] ?? 0), 'excused' => (int)($att['excused'] ?? 0)], 'incomplete' => $unlocked > 0 || !$st['complete'] || !$st['all_approved'], 'issued_at' => date('Y-m-d')];
                $json = json_encode($snap, JSON_UNESCAPED_UNICODE);
                $old = DB::one("SELECT id FROM report_cards WHERE enrollment_id = ? AND term_id IS NULL AND kind = 'final'", [$st['enrollment_id']]);
                if ($old) { DB::query('UPDATE report_cards SET snapshot = ?, average = ?, rank_in_section = ?, result = ?, issued_at = NOW(), issued_by = ? WHERE id = ?', [$json, $st['average'], $st['rank'], $st['result'], Auth::id(), $old['id']]); }
                else { DB::query("INSERT INTO report_cards (school_id, enrollment_id, term_id, kind, snapshot, average, rank_in_section, result, issued_by) VALUES (?,?,NULL,'final',?,?,?,?,?)", [$sid, $st['enrollment_id'], $json, $st['average'], $st['rank'], $st['result'], Auth::id()]); }
                $n++;
            }
        });
        AuditService::log('reportcard.issue_final', 'section', $sectionId, null, ['year' => $yearId, 'count' => $n, 'partial' => $allowIncomplete && ($unlocked || $blocked)]);
        return ['issued' => $n, 'incomplete' => count($blocked)];
    }

    public static function find(int $id): array
    {
        $portal = in_array(Auth::type(), ['guardian', 'student'], true);
        $portal ? Auth::authorize('portal.view') : Auth::authorize('grades.view');
        $r = DB::one('SELECT rc.*, e.section_id, e.student_id FROM report_cards rc JOIN student_enrollments e ON e.id = rc.enrollment_id WHERE rc.id = ? AND rc.school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الشهادة غير موجودة');
        $r['data'] = json_decode($r['snapshot'], true);
        if ($portal) {
            if (!in_array((int)$r['student_id'], PortalService::studentIds(), true) || !empty($r['data']['incomplete'])) { throw new HttpException(403, 'لا تملك صلاحية عرض هذه الشهادة'); }
        } elseif (!Scope::canSeeSection((int)$r['section_id'])) { throw new HttpException(403, 'خارج نطاقك'); }
        return $r;
    }

    public static function listForSection(int $termId, int $sectionId): array
    {
        Auth::authorize('grades.view');
        if (!Scope::canSeeSection($sectionId)) { throw new HttpException(403, 'خارج نطاقك'); }
        return DB::all("SELECT rc.id, rc.average, rc.rank_in_section, rc.result, rc.kind, s.full_name FROM report_cards rc JOIN student_enrollments e ON e.id = rc.enrollment_id JOIN students s ON s.id = e.student_id
            WHERE rc.school_id = ? AND e.section_id = ? AND ((rc.kind = 'term' AND rc.term_id = ?) OR rc.kind = 'final') ORDER BY rc.kind, rc.rank_in_section, s.full_name", [Tenant::require(), $sectionId, $termId]);
    }
}
