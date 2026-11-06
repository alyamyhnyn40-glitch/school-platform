<?php
declare(strict_types=1);
namespace App\Repositories;

final class TermRepository extends BaseRepository
{
    protected string $table = 'terms';
    protected array $fillable = ['academic_year_id','name','term_no','starts_on','ends_on','is_final','grades_locked'];
}
