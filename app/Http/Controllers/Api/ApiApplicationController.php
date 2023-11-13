<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
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

        $emp=Employee::where('user_id',auth()->user()->id)->first();

//        $leave=Application::where('employee_id',$request->employee_id)->where('start',$request->start)->where('end',$request->end)->first();
        if ($request->end) {
            $endDate = $request->end;
        } else {
            $endDate = $request->start;
        }
        $application = new Application();
        $application->approval_id=$request->approval_id;
        $application->employee_id=$emp->emp_id;
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
    public function getApplicationList(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $empId=Employee::where('user_id',auth()->user()->id)->first();
        $application=Application::where('approval_id',$empId->emp_id)->get();
        return $application;
    }
    public function getOwnApplicationList(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $empId=Employee::where('user_id',auth()->user()->id)->first();
        $application=Application::where('employee_id',$empId->emp_id)->get();
        return $application;
    }


    public function appliedList(Request $r){
//        return auth()->user()->id;
        $emp=Employee::where('user_id',auth()->user()->id)->first();
        $appList=Application::select('applications.*','employee.full_name')
            ->where('employee_id',$emp->emp_id)
            ->leftJoin('employee','employee.emp_id','applications.approval_id')
            ->get();

        $datatables = Datatables::of($appList);
        return $datatables->make(true);

    }
}