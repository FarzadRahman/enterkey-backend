<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveStatus extends Model
{
    use HasFactory;
    protected $table = 'leave_status';
    protected $primaryKey = 'l_stat_id'; // Specify the primary key column
    public $timestamps = false;
    protected $fillable = ['leave_status_name'];
}
