<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;
    protected $table = 'leave_type';
    protected $primaryKey = 'l_type_id'; // Specify the primary key column
    public $timestamps = false;
    protected $fillable = ['leave_type_name'];

}
