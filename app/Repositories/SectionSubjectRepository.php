<?php
declare(strict_types=1);
namespace App\Repositories;

final class SectionSubjectRepository extends BaseRepository
{
    protected string $table = 'section_subjects';
    protected array $fillable = ['section_id','subject_id','weekly_periods','max_score'];
}
