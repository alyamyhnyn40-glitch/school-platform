<?php
declare(strict_types=1);
namespace App\Repositories;

final class TeacherAssignmentRepository extends BaseRepository
{
    protected string $table = 'teacher_assignments';
    protected array $fillable = ['section_subject_id','staff_id','is_primary'];
}
