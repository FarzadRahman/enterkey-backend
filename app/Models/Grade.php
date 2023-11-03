<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;
    protected $table = 'grade';
    protected $primaryKey = 'gid'; // Specify the primary key column
    public $timestamps = false;
    protected $fillable = ['grade_name'];
}
