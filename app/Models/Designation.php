<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;
    protected $table = 'designation';
    protected $primaryKey = 'desg_id'; // Specify the primary key column
    public $timestamps = false;
    protected $fillable = ['desg_nm','grade_id'];
}
