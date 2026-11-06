<?php
declare(strict_types=1);
namespace App\Repositories;

final class StudentRepository extends BaseRepository
{
    protected string $table = 'students';
    protected array $fillable = ['identity_id','user_id','student_no','full_name','gender','birth_date','birth_place','nationality','national_id_enc','address','phone','blood_type','health_notes_enc','special_needs','previous_school','admission_date','status','updated_at'];
    protected bool $softDelete = true;
}
