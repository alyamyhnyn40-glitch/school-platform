<?php
declare(strict_types=1);
namespace App\Repositories;

final class GradeLevelRepository extends BaseRepository
{
    protected string $table = 'grade_levels';
    protected array $fillable = ['stage_id','name','sort_order','next_grade_level_id'];
}
