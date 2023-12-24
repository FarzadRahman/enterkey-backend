<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationPassingHistory;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\App;
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
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $Sdate = Carbon::parse($request->start)->startOfDay();
        $edate = Carbon::parse($request->end)->endOfDay();
        $days = $Sdate->diffInDays($edate);
        $total_days = $days + 1;
        $employee=Employee::where('user_id',auth()->user()->id)->first();
        if (!$employee){
            return response()->json(['message'=>'Employee not found'],404);
        }
        $totalApprovedDays=Application::select('employee_id','approved_total_days')
            ->where('status',2)
            ->where('employee_id',$employee->emp_id)
            ->whereYear('end_date',Carbon::now())
            ->sum('approved_total_days');
        if($request->leave_type_id==1 && $totalApprovedDays>=20){
            return response()->json(['message'=>'You have reached the maximum allowable leave duration of 20 days per year.','status'=>1],201);
        }
        if($request->leave_type_id==1 && $total_days>20){
           return response()->json(['message'=>'Leave duration cannot exceed 20 days','status'=>1],201);
        }
        else{
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

            $history=new ApplicationPassingHistory();
            $history->application_id=$application->id;
            $history->sender_id= $application->employee_id;
            $history->receiver_id= $application->reviewer_id;
            $history->status= 1;
            $history->save();

            activity('create')
                ->causedBy(auth()->user()->id)
                ->performedOn($application)
                ->withProperties($application)
                ->log(auth()->user()->name . ' submit application');
            return response()->json([
                'message'=>'Application submitted successfully',
                'application'=>$application
            ],201);
        }
            //return response()->json(['message'=>'another leave'],201);

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
        $appList=Application::select('applications.*','approver.full_name','recorder.full_name as recorder_name','leave_status.leave_status_name')
            ->where('employee_id',$emp->emp_id)
            ->leftJoin('employee as approver','approver.emp_id','applications.approval_id')
            ->leftJoin('employee as recorder','recorder.emp_id','applications.reviewer_id')
            ->leftJoin('leave_status','leave_status.l_stat_id','applications.status')
            ->get();

        $datatables = Datatables::of($appList);
        return $datatables->make(true);

    }

    public function getApplicationForRecorder(){
        $emp=Employee::where('user_id',auth()->user()->id)->first();

        $appList=Application::select('applications.*','approver.full_name','sender.full_name as sender_name')
            ->where('reviewer_id',$emp->emp_id)
            ->leftJoin('employee as approver','approver.emp_id','applications.approval_id')
            ->leftJoin('employee as sender','sender.emp_id','applications.employee_id')
            ->leftJoin('application_passing_history','application_passing_history.application_id','applications.id')
            ->where('application_passing_history.receiver_id',$emp->emp_id)
            ->where('application_passing_history.status',1)
            ->get();

        $datatables = Datatables::of($appList);
        return $datatables->make(true);
    }

    public function getApplicationDetails($id){
//        $application=Application::find($id);
//        if (!$application){
//            return response()->json(['message'=>'Application not found'],404);
//        }
//        $application=Application::select('applications.*','approver.full_name as approver_name','sender.full_name as sender_name','reviewer.full_name as reviewer_name')
//            ->where('id',$id)
//            ->leftJoin('employee as approver','approver.emp_id','applications.approval_id')
//            ->leftJoin('employee as sender','sender.emp_id','applications.employee_id')
//            ->leftJoin('employee as reviewer','reviewer.emp_id','applications.employee_id')
//            ->get();
//        return $application;
            $application = Application::find($id);

            if (!$application){
                return response()->json(['message'=>'Application not found'], 404);
            }

            $application = Application::select('applications.*')
                ->with(
                    [
                        'approver',
                        'approver.department',
                        'approver.branch',
                        'approver.branch.company',
                        'approver.designation',
                        'approver.designation.grade',
                        'sender',
                        'sender.department',
                        'sender.branch',
                        'sender.branch.company',
                        'sender.designation',
                        'sender.designation.grade',
                        'reviewer',
                        'reviewer.department',
                        'reviewer.branch',
                        'reviewer.branch.company',
                        'reviewer.designation',
                        'reviewer.designation.grade',
                        'leaveType'
                    ])
                ->where('id', $id)
                ->first();
        $totalApprovedDays=Application::select('employee_id','approved_total_days')
            ->where('status',2)
            ->where('employee_id',$application->employee_id)
            ->whereYear('end_date',Carbon::now())
            ->sum('approved_total_days');
        $data = [
            'application' => $application,
            'totalApprovedDays' => $totalApprovedDays,
        ];
//        $datatables = Datatables::of($application);
//        return $datatables->make(true);
            return $data;
//            return response()->json([$application,$totalApproveDate]);

    }

    public function getApplicationForApprover(){
        $emp=Employee::where('user_id',auth()->user()->id)->first();


        $appList=Application::select('applications.*','sender.full_name','recorder.full_name as recorder_name')
            ->where('approval_id',$emp->emp_id)
            ->leftJoin('employee as sender','sender.emp_id','applications.employee_id')
            ->leftJoin('employee as recorder','recorder.emp_id','applications.reviewer_id')
            ->leftJoin('application_passing_history','application_passing_history.application_id','applications.id')
            ->where('application_passing_history.receiver_id',$emp->emp_id)
            ->where('application_passing_history.status',1)
            ->get();



        $datatables = Datatables::of($appList);
        return $datatables->make(true);
    }
    public function applicationApproved(Request $request, $id){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }

        $emp = Employee::where('user_id', auth()->user()->id)->first();

        // Find the application
        $application = Application::where('approval_id', $emp->emp_id)->where('id',$id)->first();
        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Set approved dates and total days based on reviewer start date
        if ($application->reviewer_start_date) {
            $application->approved_total_days = $application->review_total_days;
            $application->approved_start_date = $application->reviewer_start_date;
            $application->approved_end_date = $application->reviewer_end_date;
        } else {
            $application->approved_total_days = $application->applied_total_days;
            $application->approved_start_date = $application->start_date;
            $application->approved_end_date = $application->end_date;
        }

        // Set comments if provided
        if ($request->comments) {
            $application->comment = $request->comments;
        }

        // Update application status and save changes
        $application->status = 2;
        $application->save();

        // Update ApplicationPassingHistory
        ApplicationPassingHistory::where('application_id', $id)->update(['status' => 2]);

        $appPassHistory = new ApplicationPassingHistory();
        $appPassHistory->application_id = $application->id;
        $appPassHistory->sender_id = $application->reviewer_id;
        $appPassHistory->receiver_id = $emp->emp_id;
        $appPassHistory->comments = $request->comments;
        $appPassHistory->status = 2;
        $appPassHistory->save();

        // Log the approval action
        activity('approved')
            ->causedBy(auth()->user()->id)
            ->performedOn($application)
            ->withProperties($application)
            ->log(auth()->user()->name . ' approved application');

        return response()->json([
            'message' => 'Application approved successfully',
            'application' => $application
        ], 201);
    }

    public function applicationPass(Request $request, $id)
    {

        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }

        $empId = Employee::where('user_id', auth()->user()->id)->first();

        // Ensure Employee data is available
        if (!$empId) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $application = Application::find($id);



        if (!$application )//|| !($application->approval_id === $empId->emp_id || $application->reviewer_id === $empId->emp_id)) {
        {
            return response()->json(['message' => 'Application not found '], 404);
        }



        if ($application->approval_id==$empId->emp_id){
            //            If User is Approver
//            return response()->json(['message' => 'I am Approver'], 201);
            ApplicationPassingHistory::where('application_id', $id)->update(['status' => 2]);

            $appPassHistory = new ApplicationPassingHistory();
            $appPassHistory->application_id = $application->id;
            $appPassHistory->sender_id = $empId->emp_id;
            $appPassHistory->receiver_id = $application->employee_id;
            $appPassHistory->status = 2;
            $appPassHistory->comments = $request->comments;
            $appPassHistory->save();

            $application->status=2;
            $application->approved_total_days=$application->applied_total_days;
            $application->save();

            activity('pass')
                ->causedBy(auth()->user()->id)
                ->performedOn($application)
                ->withProperties($application)
                ->log(auth()->user()->name . ' pass application');
        }

        elseif ($application->reviewer_id==$empId->emp_id){
//            If User is recorder
//            return response()->json(['message' => 'I am recorder'], 201);
            ApplicationPassingHistory::where('application_id', $id)->update(['status' => 2]);
            $appPassHistory = new ApplicationPassingHistory();
            $appPassHistory->application_id = $application->id;
            $appPassHistory->sender_id = $empId->emp_id;
            $appPassHistory->receiver_id = $application->approval_id;
            $appPassHistory->status = 1;
            $appPassHistory->comments = $request->comments;
            $appPassHistory->save();
            activity('pass')
                ->causedBy(auth()->user()->id)
                ->performedOn($appPassHistory)
                ->withProperties($appPassHistory)
                ->log(auth()->user()->name . ' pass application');
        }


        return response()->json(['message' => 'Application pass successful'], 201);
    }

    public function approveLeaveCount(){
        $empId = Employee::where('user_id', auth()->user()->id)->first();
        return Application::where('employee_id',$empId->emp_id)->where('status',2)->sum('approved_total_days');
    }

    public function applicationReturn(Request $request, $id)
    {

        $empId = Employee::where('user_id', auth()->user()->id)->first();

        if (!$empId) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $application = Application::find($id);

        if (!$application)// || !($application->approval_id === $empId->emp_id || $application->reviewer_id === $empId->emp_id)) {
        {
            return response()->json(['message' => 'Application not found '], 404);
        }


            ApplicationPassingHistory::where('application_id', $id)->update(['status' => 2]);

            $appPassHistory = new ApplicationPassingHistory();
            $appPassHistory->application_id = $application->id;
            $appPassHistory->sender_id = $empId->emp_id;
            $appPassHistory->receiver_id = $application->employee_id;
            $appPassHistory->status = 1;
            $appPassHistory->comments = $request->comments;
            $appPassHistory->save();

            $application->status=3;
            $application->save();



        activity('return')
            ->causedBy(auth()->user()->id)
            ->performedOn($application)
            ->withProperties($application)
            ->log(auth()->user()->name . ' return application');



        return response()->json(['message' => 'Application return successfully'], 201);
    }

    public function applicationCancel(Request $request, $id)
    {

        $empId = Employee::where('user_id', auth()->user()->id)->first();

        if (!$empId) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $application = Application::find($id);

        if (!$application)// || !($application->approval_id === $empId->emp_id || $application->reviewer_id === $empId->emp_id)) {
        {
            return response()->json(['message' => 'Application not found '], 404);
        }


        ApplicationPassingHistory::where('application_id', $id)->update(['status' => 2]);

        $appPassHistory = new ApplicationPassingHistory();
        $appPassHistory->application_id = $application->id;
        $appPassHistory->sender_id = $empId->emp_id;
        $appPassHistory->receiver_id = $application->employee_id;
        $appPassHistory->status = 1;
        $appPassHistory->comments = $request->comments;
        $appPassHistory->save();
        $application->status=4;
        $application->save();


        activity('cancel')
            ->causedBy(auth()->user()->id)
            ->performedOn($application)
            ->withProperties($application)
            ->log(auth()->user()->name . ' reject application');



        return response()->json(['message' => 'Application return successfully'], 201);
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
        $employee=Employee::where('user_id',auth()->user()->id)->first();
        if (!$employee){
            return response()->json(['message'=>'Employee not found'],404);
        }
        $totalApprovedDays=Application::select('employee_id','approved_total_days')
            ->where('status',2)
            ->where('employee_id',$employee->emp_id)
            ->whereYear('end_date',Carbon::now())
            ->sum('approved_total_days');
        if($request->leave_type_id==1 && $totalApprovedDays>=20){
            return response()->json(['message'=>'You have reached the maximum allowable leave duration of 20 days per year.','status'=>1],201);
        }
        if($request->leave_type_id==1 && $total_days>20){
            return response()->json(['message'=>'Leave duration cannot exceed 20 days','status'=>1],201);
        }
        else{
            $application->status = 1;

            $application->save();

            ApplicationPassingHistory::where('application_id', $id)->update(['status' => 2]);

            $appPassHistory = new ApplicationPassingHistory();
            $appPassHistory->application_id = $application->id;
            $appPassHistory->sender_id = $empId->emp_id;
            $appPassHistory->receiver_id = $application->reviewer_id;
            $appPassHistory->status = 1;
            if ($request->has('comments')){
                $appPassHistory->comments = $request->comments;
            }
            $appPassHistory->save();
            activity('update')
                ->causedBy(auth()->user()->id)
                ->performedOn($application)
                ->withProperties($application)
                ->log(auth()->user()->name . ' updated application');
            return response()->json([
                'message' => 'Application updated successfully',
                'application' => $application
            ], 201);
        }
    }
    public function applicationHistory($id){
        $application=Application::find($id);
        if (!$application){
            return response()->json(['message'=>'Application not found'],404);
        }
        $appHistory=ApplicationPassingHistory::
                with(
                    [
                        'application',
                        'sender',
                        'sender.user',
                        'receiver',
                        'receiver.user'
                    ])
                ->where('application_id',$id)
                ->get();

        return $appHistory;
    }

}
