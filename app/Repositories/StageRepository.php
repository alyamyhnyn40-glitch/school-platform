<?php
declare(strict_types=1);
namespace App\Repositories;

final class StageRepository extends BaseRepository
{
    protected string $table = 'stages';
    protected array $fillable = ['name','sort_order'];
}
