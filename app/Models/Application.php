<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;
    public function approver() {
        return $this->belongsTo(Employee::class, 'approval_id', 'emp_id');
    }

    public function sender() {
        return $this->belongsTo(Employee::class, 'employee_id', 'emp_id');
    }

    public function reviewer() {
        return $this->belongsTo(Employee::class, 'reviewer_id', 'emp_id');
    }
    public function leaveType() {
        return $this->belongsTo(LeaveType::class, 'leave_type', 'l_type_id');
    }
}
