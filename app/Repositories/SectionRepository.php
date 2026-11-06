<?php
declare(strict_types=1);
namespace App\Repositories;

final class SectionRepository extends BaseRepository
{
    protected string $table = 'sections';
    protected array $fillable = ['academic_year_id','grade_level_id','name','gender','capacity','homeroom_staff_id','room_id'];
}
