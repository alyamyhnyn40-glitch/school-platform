<?php
declare(strict_types=1);
namespace App\Repositories;

final class EnrollmentRepository extends BaseRepository
{
    protected string $table = 'student_enrollments';
    protected array $fillable = ['student_id','academic_year_id','section_id','roll_no','status','enrolled_on','left_on'];
}
