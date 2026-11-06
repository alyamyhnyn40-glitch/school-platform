<?php
declare(strict_types=1);
namespace App\Repositories;

final class RoomRepository extends BaseRepository
{
    protected string $table = 'rooms';
    protected array $fillable = ['name','capacity','kind'];
}
