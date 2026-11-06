<?php
declare(strict_types=1);
namespace App\Repositories;

final class StatusHistoryRepository extends BaseRepository
{
    protected string $table = 'student_status_history';
    protected array $fillable = ['student_id','academic_year_id','from_status','to_status','reason','changed_by'];
}
