<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Message;
use Illuminate\Http\Request;

class ApiMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function getMessages(){
        $employee=Employee::where('user_id',auth()->user()->id)->first();
        $messages=Message::with
        (
            [
                'sender.branch.company',
                'sender.designation',
                'sender.department',
                'receiver.branch.company',
                'receiver.designation',
                'receiver.department',
                'application.sender.branch.company',
                'application.sender.designation',
                'application.sender.department',
                'application.approver.branch.company',
                'application.approver.designation',
                'application.approver.department',
                'application.reviewer.branch.company',
                'application.reviewer.designation',
                'application.reviewer.department',
                'application.leaveType',
                'application.leaveStatus'
            ]
        )->where('receiver_id',$employee->emp_id)->latest()->paginate(10);
        return $messages;
    }
}
