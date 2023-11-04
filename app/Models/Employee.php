<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $table = 'employee';
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
}
