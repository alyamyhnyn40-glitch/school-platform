<?php
declare(strict_types=1);
namespace App\Repositories;

final class AssessmentRepository extends BaseRepository
{
    protected string $table = 'assessments';
    protected array $fillable = ['term_id', 'section_subject_id', 'type_id', 'title', 'max_score', 'held_on', 'status', 'approved_by', 'locked_at', 'created_by'];
}
