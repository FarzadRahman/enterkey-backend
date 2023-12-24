<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class ApiReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function data(Request $r)
    {
//        return $r;
//
      $application=Application::with
      (
          [
              'sender','sender.user','sender.designation',
              'approver','approver.user','approver.designation',
              'reviewer','reviewer.user',
              'leaveType',
              'leaveStatus'
          ]
      );

      if($r->leaveType){
          $application=$application->where('leave_type',$r->leaveType);
      }
      if($r->selectedEmp){
          $application=$application->where('employee_id',$r->selectedEmp);
      }

      if($r->leaveStartDate){ $application=$application->where('start_date','>=',$r->leaveStartDate);}
      if($r->leaveEndDate){ $application=$application->where('end_date','<=',$r->leaveEndDate);}

      $application=$application->paginate(10);
      return $application;
    }

}
