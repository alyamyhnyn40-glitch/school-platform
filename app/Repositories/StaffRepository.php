<?php
declare(strict_types=1);
namespace App\Repositories;

final class StaffRepository extends BaseRepository
{
    protected string $table = 'staff';
    protected array $fillable = ['user_id','employee_no','full_name','gender','birth_date','national_id_enc','phone','email','address','job_title','specialization','is_teacher','hire_date','status','updated_at','deleted_at'];
    protected bool $softDelete = true;
}
