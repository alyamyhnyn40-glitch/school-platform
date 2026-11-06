<?php
declare(strict_types=1);
namespace App\Repositories;

final class StudentGuardianRepository extends BaseRepository
{
    protected string $table = 'student_guardians';
    protected array $fillable = ['student_id','guardian_id','relation','is_primary','can_view','can_pickup','restriction_note','financially_responsible'];
}
