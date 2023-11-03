<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $table = 'company';
    protected $primaryKey = 'comp_id'; // Specify the primary key column
    public $timestamps = false;
    protected $fillable = ['company_name'];
}
