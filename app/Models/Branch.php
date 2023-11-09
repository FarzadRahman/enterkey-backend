<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    protected $table = 'branch';
    protected $primaryKey = 'bran_id'; // Specify the primary key column
    public $timestamps = false;
    protected $fillable = ['branch_name', 'company_id'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
