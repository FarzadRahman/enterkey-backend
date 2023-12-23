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

      $application=Application::with
      (
          [
              'sender','sender.user',
              'approver','approver.user',
              'reviewer','reviewer.user',
              'leaveType',
              'leaveStatus'
          ]
      )->paginate(10);
      return $application;
    }

}
