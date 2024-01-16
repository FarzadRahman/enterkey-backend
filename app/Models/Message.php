<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';
    protected $primaryKey = 'msg_id'; // Specify the primary key column
    public $timestamps = false;
    protected $fillable = ['sender_id','receiver_id','message','url','created_at'];

    public function sender() {
        return $this->belongsTo(Employee::class, 'sender_id', 'emp_id');
    }
    public function receiver() {
        return $this->belongsTo(Employee::class, 'receiver_id', 'emp_id');
    }
    public function application() {
        return $this->belongsTo(Application::class, 'application_id', 'id');
    }



}
