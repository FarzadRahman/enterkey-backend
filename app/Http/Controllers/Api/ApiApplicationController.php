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
        $application->reviewer_id=$request->reviewer_id;
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
            ->where('employee.isApprover',1)
            ->get();
        return $employeeList;
    }

    public function getRecorderEmployee(){

        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employeeList= Employee::leftjoin('users','users.id','employee.user_id')
            ->where('user_id','!=',auth()->user()->id)
            ->where('users.company','=',auth()->user()->company)
            ->where('employee.isRecorder',1)
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

    public function getApplicationForRecorder(){
        $emp=Employee::where('user_id',auth()->user()->id)->first();
        $appList=Application::select('applications.*','employee.full_name')
            ->where('reviewer_id',$emp->emp_id)
            ->leftJoin('employee','employee.emp_id','applications.approval_id')
            ->get();

        return $appList;
    }

    public function getApplicationDetails($id){
        $application=Application::find($id);

        return $application;
    }

    public function getApplicationForApprover(){
        $emp=Employee::where('user_id',auth()->user()->id)->first();
        $appListApplication=Application::select('applications.*','employee.full_name')
            ->where('approval_id',$emp->emp_id)
            ->leftJoin('employee','employee.emp_id','applications.approval_id')
            ->get();

        return $appListApplication;
    }
    public function applicationApproved(Request $request,$id){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $emp=Employee::where('user_id',auth()->user()->id)
            ->where('company',auth()->user()->company)->first();

        $application=Application::where('approval_id',$emp->id)->find($id);
//        $appid = Application::select('employee_id')->where('approval_id',$emp->id)->find($id);

        // $application->approved_total_days= $application->applied_total_days;
        // $application->approved_start_date= $application->start_date;
        // $application->approved_end_date= $application->end_date;
        if($application->reviewer_start_date){
            $application->approved_total_days= $application->review_total_days;
            $application->approved_start_date= $application->reviewer_start_date;
            $application->approved_end_date= $application->reviewer_end_date;
        }
        else
        {
            $application->approved_total_days= $application->applied_total_days;
            $application->approved_start_date= $application->start_date;
            $application->approved_end_date= $application->end_date;

        }
        $application->status= 2;
       // $application->company=auth()->user()->company;
        $application->save();

        return response()->json([
            'message'=>'Application approved successfully',
            'application'=>$application
        ],201);
    }
    public function applicationPass(Request $request,$id){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $empId=Employee::where('user_id',auth()->user()->id)->first();
        $application = Application::where('id', $id)
            ->where(function ($query) use ($empId) {
                $query->where('approval_id', $empId->emp_id)
                    ->orWhere('reviewer_id', $empId->emp_id);
            })
            ->first();

        if(!$application){
            return response()->json(['message'=>'Application not found'],404);
        }
        $application->approval_id=$request->approval_id;
        $application->save();
        return response()->json([
            'message'=>'Application pass successfully',
            'application'=>$application
        ],201);
    }
    public function editApplicationDetails(Request $request, $id){
        $empId = Employee::where('user_id', auth()->user()->id)->first();

        $application = Application::where('id', $id)
            ->where('employee_id', $empId->emp_id)
            ->first();

        if (!$application){
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Update the application details if the fields are available in the request
        if ($request->has('approval_id')) {
            $application->approval_id = $request->approval_id;
        }

        if ($request->has('reviewer_id')) {
            $application->reviewer_id = $request->reviewer_id;
        }

        if ($request->has('start')) {
            $Sdate = Carbon::parse($request->start)->startOfDay();
            $application->start_date = $Sdate;
        }

        if ($request->has('end')) {
            $edate = Carbon::parse($request->end)->endOfDay();
            $application->end_date = $edate;
        }

        if ($request->has('reason')) {
            $application->reason = $request->reason;
        }

        if ($request->has('stay_location')) {
            $application->stay_location = $request->stay_location;
        }

        if ($request->has('leave_type_id')) {
            $application->leave_type = $request->leave_type_id;
        }

        if ($request->has('start') && $request->has('end')) {
            $Sdate = Carbon::parse($request->start)->startOfDay();
            $edate = Carbon::parse($request->end)->endOfDay();
            $days = $Sdate->diffInDays($edate);
            $total_days = $days + 1;
            $application->applied_total_days = $total_days;
        }

        $application->status = 1;

        $application->save();

        return response()->json([
            'message' => 'Application updated successfully',
            'application' => $application
        ], 201);
    }

}
