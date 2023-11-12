<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Employee;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }

    public function store(Request $request){
        $Sdate = Carbon::parse($request->start)->startOfDay();
        $edate = Carbon::parse($request->end)->endOfDay();
        $days = $Sdate->diffInDays($edate);
        $total_days = $days + 1;

//        $leave=Application::where('employee_id',$request->employee_id)->where('start',$request->start)->where('end',$request->end)->first();
        if ($request->end) {
            $endDate = $request->end;
        } else {
            $endDate = $request->start;
        }
        $application = new Application();
        $application->approval_id=$request->approval_id;
        $application->employee_id=auth()->user()->id;
        $application->start_date=$request->start;
        $application->end_date=$endDate;
        $application->applied_total_days=$total_days;
        $application->reason=$request->reason;
        $application->stay_location=$request->stay_location;
        $application->leave_type=$request->leave_type_id;
        $application->status=1;

        $application->save();

        return response()->json([
            'message'=>'Application submitted successfully',
            'application'=>$application
        ],201);
    }
    public function getLeaveEmployee(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employeeList= Employee::leftjoin('users','users.id','employee.user_id')
            ->where('user_id','!=',auth()->user()->id)
            ->where('users.company','=',auth()->user()->company)
            ->get();
        return $employeeList;
    }
}
