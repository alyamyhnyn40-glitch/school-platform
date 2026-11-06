<?php
declare(strict_types=1);
namespace App\Repositories;

final class DisciplineRepository extends BaseRepository
{
    protected string $table = 'discipline_records';
    protected array $fillable = ['student_id','incident_date','kind','description','action_taken','recorded_by','deleted_at'];
    protected bool $softDelete = true;
}
