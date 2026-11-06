<?php
declare(strict_types=1);
namespace App\Repositories;

final class SubjectRepository extends BaseRepository
{
    protected string $table = 'subjects';
    protected array $fillable = ['name','code'];
}
