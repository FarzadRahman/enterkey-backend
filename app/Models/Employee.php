<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Designation;
class Employee extends Model
{
    use HasFactory;
    protected $table = 'employee';
    protected $primaryKey='emp_id';
    protected $fillable = [
        'full_name',
        'gender',
        'phone_number',
        'email_address',
        'office_id',
        'branch_id',
        'user_id',
        'designation_id',
        'department_id',
        'signature',
    ];
    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
