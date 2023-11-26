<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationPassingHistory extends Model
{
    use HasFactory;

    public $table='application_passing_history';
    public function application() {
        return $this->belongsTo(Application::class, 'application_id');
    }
    public function sender() {
        return $this->belongsTo(Employee::class, 'sender_id','emp_id');
    }
    public function receiver() {
        return $this->belongsTo(Employee::class, 'receiver_id','emp_id');
    }

}
