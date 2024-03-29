<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationPassingHistory;
use App\Models\Employee;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
class ApiApplicationController extends Controller
{
    const acceptedPdfTypes =  [
        "application/pdf"
    ];

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
        $validator = Validator::make($request->all(), [
//            'approval_id' => 'required', // Add appropriate rules for this field
//            'reason' => 'required|string', // Add appropriate rules for this field
            'leave_type_id' => 'required', // Add appropriate rules for this field
            'start' => 'required|date', // Add appropriate rules for this field
            'end' => 'required|date|after_or_equal:start', // Add appropriate rules for this field, ensuring 'end' is after or equal to 'start'
            'stay_location' => 'required|string', // Add appropriate rules for this field
//            'reviewer_id' => 'required', // Add appropriate rules for this field
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
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
            if($request->approval_id){
                $application->approval_id=$request->approval_id;
            }
            $application->reviewer_id=$request->reviewer_id;
            $application->employee_id=$emp->emp_id;
            $application->start_date=$request->start;
            $application->end_date=$endDate;
            $application->applied_total_days=$total_days;
            $application->reason=$request->reason;
            $application->stay_location=$request->stay_location;
            $application->leave_type=$request->leave_type_id;
            $application->status=1;
            if($request->files){
                $requestFile = $request->input('files'); // Replace 'base64_data' with the actual field name in your form

                // Extract MIME type from the base64 string
                preg_match('#^data:(.*?);base64,#i', $requestFile, $matches);
                $mimeType = $matches[1] ?? null;

                // Check if the MIME type is in the accepted types
                if (!in_array($mimeType, self::acceptedPdfTypes)) {
                    $application->files=null;
                }
                else{
                    // Remove the data URI scheme and header from the base64 string
                    $base64Data = preg_replace('#^data:' . preg_quote($mimeType) . ';base64,#i', '', $requestFile);

                    // Decode the base64 data
                    $decodedData = base64_decode($base64Data);

                    // Specify the file name
                    $fileName =  time().'-'.$request->fileName; // You can customize the file name if needed
                    $application->files=$fileName;
                    // Save the decoded data as a PDF file, overwriting the existing file if it exists
                    file_put_contents(public_path().'/uploads/'.$fileName, $decodedData);

                }


            }


            $application->save();

            $message=new Message();
            $message->sender_id=$application->employee_id;
            $message->receiver_id=$application->reviewer_id;
            $message->application_id=$application->id;
            $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.' applied an application';
            $message->url="/leave/details/".$application->id;
            $message->created_at=Carbon::now();
            $message->save();

            $message=new Message();
            $message->sender_id=$application->employee_id;
            $message->receiver_id=$application->employee_id;
            $message->application_id=$application->id;
            $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.' applied an application';
            $message->url="/leave/details/".$application->id;
            $message->created_at=Carbon::now();
            $message->save();


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
    public function setApprover(Request $request,$id){
//        return $request->all();
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employee=Employee::where('user_id',auth()->user()->id)->first();
        $application=Application::where('id',$id)->first();
        if(!$application){
            return response()->json(['message'=>'Application not found'],404);
        }
        //approval_id
        //approval_id
        $application->approval_id=$request->approval_id;
        $application->save();

        activity('update')
            ->causedBy(auth()->user()->id)
            ->performedOn($application)
            ->withProperties($application)
            ->log(auth()->user()->name . ' selected approver');
        return response()->json([
            'message'=>'Approver is selected',
            'data'=>$application
        ],200);
    }
    public function getLeaveEmployee(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employeeList= Employee::leftjoin('users','users.id','employee.user_id')
            ->leftjoin('designation','employee.designation_id','designation.desg_id')
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
            ->leftjoin('designation','employee.designation_id','designation.desg_id')
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
//        $appList=Application::select('applications.*','approver.full_name','recorder.full_name as recorder_name','leave_status.leave_status_name')
//            ->where('employee_id',$emp->emp_id)
//            ->leftJoin('employee as approver','approver.emp_id','applications.approval_id')
//            ->leftJoin('employee as recorder','recorder.emp_id','applications.reviewer_id')
//            ->leftJoin('leave_status','leave_status.l_stat_id','applications.status')
//            ->get();
//
//        $datatables = Datatables::of($appList);
//        return $datatables->make(true);
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
        $application=$application->leftJoin('employee','employee.emp_id','applications.employee_id')
            ->leftJoin('users','users.id','employee.user_id')
            ->where('employee_id',$emp->emp_id)
            ->select('applications.*','users.company');
        if($r->leaveType){
            $application=$application->where('leave_type',$r->leaveType);
        }
        if($r->selectedEmp){
            $application=$application->where('employee_id',$r->selectedEmp);
        }
        if($r->leaveStatus){
            $application=$application->where('status',$r->leaveStatus);
        }
        if($r->leaveStartDate){ $application=$application->where('start_date','>=',$r->leaveStartDate);}
        if($r->leaveEndDate){ $application=$application->where('end_date','<=',$r->leaveEndDate);}

        if(auth()->user()->role_id>1){
            $application=$application->where('users.company',auth()->user()->company)->paginate(10);
        }
        else{
            $application=$application->paginate(10);
        };
        return $application;

    }

    public function getApplicationForRecorder(Request $r){
        $emp=Employee::where('user_id',auth()->user()->id)->first();

//        $appList=Application::select('applications.*','approver.full_name','sender.full_name as sender_name')
//            ->where('reviewer_id',$emp->emp_id)
//            ->leftJoin('employee as approver','approver.emp_id','applications.approval_id')
//            ->leftJoin('employee as sender','sender.emp_id','applications.employee_id')
//            ->leftJoin('application_passing_history','application_passing_history.application_id','applications.id')
//            ->where('application_passing_history.receiver_id',$emp->emp_id)
//            ->where('application_passing_history.status',1)
//            ->get();
//
//        $datatables = Datatables::of($appList);
//        return $datatables->make(true);


        $application=Application::
            with([
            'sender','sender.user','sender.designation',
            'approver','approver.user','approver.designation',
            'reviewer','reviewer.user',
            'leaveType',
            'leaveStatus'
        ])
        ->select('applications.*')
            ->where('reviewer_id',$emp->emp_id)
            ->leftJoin('employee as approver','approver.emp_id','applications.approval_id')
            ->leftJoin('employee as sender','sender.emp_id','applications.employee_id')
            ->leftJoin('application_passing_history','application_passing_history.application_id','applications.id')
            ->where('application_passing_history.receiver_id',$emp->emp_id)
            ->where('application_passing_history.status',1);

        if($r->leaveType){
            $application=$application->where('leave_type',$r->leaveType);
        }
        if($r->selectedEmp){
            $application=$application->where('employee_id',$r->selectedEmp);
        }
        if($r->leaveStatus){
            $application=$application->where('status',$r->leaveStatus);
        }
        if($r->leaveStartDate){ $application=$application->where('start_date','>=',$r->leaveStartDate);}
        if($r->leaveEndDate){ $application=$application->where('end_date','<=',$r->leaveEndDate);}


        $application=$application->paginate(10);

        return $application;

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
                        'approver.user',
                        'approver.department',
                        'approver.branch',
                        'approver.branch.company',
                        'approver.designation',
                        'approver.designation.grade',
                        'sender',
                        'sender.user',
                        'sender.department',
                        'sender.branch',
                        'sender.branch.company',
                        'sender.designation',
                        'sender.designation.grade',
                        'reviewer',
                        'reviewer.user',
                        'reviewer.department',
                        'reviewer.branch',
                        'reviewer.branch.company',
                        'reviewer.designation',
                        'reviewer.designation.grade',
                        'leaveType',
                        'leaveStatus'
                    ])
                ->where('id', $id)
                ->first();
            if($application->leave_type===1){
                $totalApprovedDays=Application::select('employee_id','approved_total_days')
                    ->where('status',2)
                    ->where('leave_type',1)
                    ->where('employee_id',$application->employee_id)
                    ->whereYear('end_date',Carbon::now())
                    ->sum('approved_total_days');
            }
            else{
                $totalApprovedDays=Application::select('employee_id','approved_total_days')
                    ->where('status',2)
                    ->where('employee_id',$application->employee_id)
                    ->whereYear('end_date',Carbon::now())
                    ->sum('approved_total_days');
            }

        $data = [
            'application' => $application,
            'totalApprovedDays' => $totalApprovedDays,
        ];
//        $datatables = Datatables::of($application);
//        return $datatables->make(true);
            return $data;
//            return response()->json([$application,$totalApproveDate]);

    }

    public function getApplicationForApprover(Request $r){
        $emp=Employee::where('user_id',auth()->user()->id)->first();


//        $appList=Application::select('applications.*','sender.full_name','recorder.full_name as recorder_name')
//            ->where('approval_id',$emp->emp_id)
//            ->leftJoin('employee as sender','sender.emp_id','applications.employee_id')
//            ->leftJoin('employee as recorder','recorder.emp_id','applications.reviewer_id')
//            ->leftJoin('application_passing_history','application_passing_history.application_id','applications.id')
//            ->where('application_passing_history.receiver_id',$emp->emp_id)
//            ->where('application_passing_history.status',1)
//            ->get();



//        $datatables = Datatables::of($appList);
//        return $datatables->make(true);
        $application=Application::
        with([
            'sender','sender.user','sender.designation',
            'approver','approver.user','approver.designation',
            'reviewer','reviewer.user',
            'leaveType',
            'leaveStatus'
        ])
            ->select('applications.*')
            ->where('approval_id',$emp->emp_id)
            ->leftJoin('employee as sender','sender.emp_id','applications.employee_id')
            ->leftJoin('employee as recorder','recorder.emp_id','applications.reviewer_id')
            ->leftJoin('application_passing_history','application_passing_history.application_id','applications.id')
            ->where('application_passing_history.receiver_id',$emp->emp_id)
            ->where('application_passing_history.status',1);
        if($r->leaveType){
            $application=$application->where('leave_type',$r->leaveType);
        }
        if($r->selectedEmp){
            $application=$application->where('employee_id',$r->selectedEmp);
        }
        if($r->leaveStatus){
            $application=$application->where('status',$r->leaveStatus);
        }
        if($r->leaveStartDate){ $application=$application->where('start_date','>=',$r->leaveStartDate);}
        if($r->leaveEndDate){ $application=$application->where('end_date','<=',$r->leaveEndDate);}


        $application=$application->paginate(10);

        return $application;

    }
    public function ToMeApplicationList(Request $r){
//        return Carbon::now();
        $emp=Employee::where('user_id',auth()->user()->id)->first();


//        $appList=ApplicationPassingHistory::
//            select('applications.*','application_passing_history.*')
//            ->leftJoin('applications','applications.id','application_passing_history.application_id')
//            ->with(['application'])
//
//            ->where('receiver_id',$emp->emp_id)
//            ->get();
        $application = Application::
        join('application_passing_history', 'application_passing_history.application_id', 'applications.id')
            ->with([
                'approver',
                'approver.user',
                'approver.department',
                'approver.branch',
                'approver.branch.company',
                'approver.designation',
                'approver.designation.grade',
                'sender',
                'sender.user',
                'sender.department',
                'sender.branch',
                'sender.branch.company',
                'sender.designation',
                'sender.designation.grade',
                'reviewer',
                'reviewer.user',
                'reviewer.department',
                'reviewer.branch',
                'reviewer.branch.company',
                'reviewer.designation',
                'reviewer.designation.grade',
                'leaveType','leaveStatus'
            ])
            ->select('applications.*')
            ->whereNot('applications.employee_id',$emp->emp_id)
            ->where('application_passing_history.receiver_id', $emp->emp_id)
            ->distinct();
            if($r->leaveType){
                $application=$application->where('leave_type',$r->leaveType);
            }
            if($r->selectedEmp){
                $application=$application->where('employee_id',$r->selectedEmp);
            }
            if($r->leaveStatus){
                $application=$application->where('status',$r->leaveStatus);
            }
            if($r->leaveStartDate){ $application=$application->where('start_date','>=',$r->leaveStartDate);}
            if($r->leaveEndDate){ $application=$application->where('end_date','<=',$r->leaveEndDate);}


            $application=$application->paginate(10);

        return $application;

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

        if($request->start){
            $application->approved_start_date=$request->start;
            $application->approved_end_date=$request->end;
            $application->approved_total_days=$request->approved_total_days;
        }
        else{
            if ($application->reviewer_start_date) {
                $application->approved_total_days = $application->review_total_days;
                $application->approved_start_date = $application->reviewer_start_date;
                $application->approved_end_date = $application->reviewer_end_date;
            } else {
                $application->approved_total_days = $application->applied_total_days;
                $application->approved_start_date = $application->start_date;
                $application->approved_end_date = $application->end_date;
            }
        }

        // Set comments if provided
        if ($request->comments) {
            $application->comment = $request->comments;
        }

        // Update application status and save changes
        $application->status = 2;
        $application->save();

        $message=new Message();
        $message->sender_id=$emp->emp_id;
        $message->receiver_id=$application->employee_id;
        $message->application_id=$application->id;
        $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.' approved your application';
        $message->url="/leave/details/".$application->id;
        $message->created_at=Carbon::now();
        $message->save();

        // Update ApplicationPassingHistory
        ApplicationPassingHistory::where('application_id', $id)->update(['status' => 2]);

        $appPassHistory = new ApplicationPassingHistory();
        $appPassHistory->application_id = $application->id;
        $appPassHistory->sender_id = $emp->emp_id;
        $appPassHistory->receiver_id = $application->employee_id;
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

            if($request->start){
                $application->approved_start_date=$request->start;
                $application->approved_end_date=$request->end;
                $application->approved_total_days=$request->approved_total_days;
            }
            else{
                $application->approved_start_date=$application->start_date;
                $application->approved_end_date=$application->end_date;
                $application->approved_total_days=$application->applied_total_days;
            }
            $application->status=2;
            $application->save();

            $message=new Message();
            $message->sender_id=$empId->emp_id;
            $message->receiver_id=$application->employee_id;
            $message->application_id=$application->id;
            $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.'approved your application';
            $message->url="/leave/details/".$application->id;
            $message->created_at=Carbon::now();
            $message->save();

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
            if($request->start){
                $application->reviewer_start_date=$request->start;
                $application->reviewer_end_date = $request->end;
                $application->review_total_days=$request->approved_total_days;
            }
            $application->status=5;
            $application->save();

            $message=new Message();
            $message->sender_id=$empId->emp_id;
            $message->receiver_id=$application->approval_id;
            $message->application_id=$application->id;
            $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.' applied an application';
            $message->url="/leave/details/".$application->id;
            $message->created_at=Carbon::now();
            $message->save();

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

    public function approveLeaveCountWithRemainingDays(){
        $empId = Employee::where('user_id', auth()->user()->id)->first();
        $totalApprovedDays = Application::select('leave_type', DB::raw('SUM(approved_total_days) as approved_total_days'))
            ->with('leaveType')
            ->where('status', 2)
            ->where('employee_id', $empId->emp_id)
            ->whereYear('end_date', Carbon::now())
            ->groupBy('leave_type')
            ->get();
        $result = $totalApprovedDays->map(function ($item) {
            if($item->leave_type==1){
                $remainingDays = 20 - $item->approved_total_days;
            }
            else{
                $remainingDays='';
            }
            return [
                'leave_type' => $item->leaveType,
                'approved_total_days' => $item->approved_total_days,
                'remainingDays' => $remainingDays
            ];
        });
        return response()->json(['totalApprovedDays'=>$result],200);
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

        $message=new Message();
        $message->sender_id=$empId->emp_id;
        $message->receiver_id=$application->employee_id;
        $message->application_id=$application->id;
        $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.' returned your application';
        $message->url="/leave/details/".$application->id;
        $message->created_at=Carbon::now();
        $message->save();



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

        $message=new Message();
        $message->sender_id=$empId->emp_id;
        $message->receiver_id=$application->employee_id;
        $message->application_id=$application->id;
        $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.' rejected your application';
        $message->url="/leave/details/".$application->id;
        $message->created_at=Carbon::now();
        $message->save();


        activity('cancel')
            ->causedBy(auth()->user()->id)
            ->performedOn($application)
            ->withProperties($application)
            ->log(auth()->user()->name . ' reject application');



        return response()->json(['message' => 'Application reject successfully'], 201);
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
            if($request->files){
                $requestFile = $request->input('files'); // Replace 'base64_data' with the actual field name in your form

                // Extract MIME type from the base64 string
                preg_match('#^data:(.*?);base64,#i', $requestFile, $matches);
                $mimeType = $matches[1] ?? null;

                // Check if the MIME type is in the accepted types
                if (!in_array($mimeType, self::acceptedPdfTypes)) {
                    $application->files=null;
                }
                else{
                    // Remove the data URI scheme and header from the base64 string
                    $base64Data = preg_replace('#^data:' . preg_quote($mimeType) . ';base64,#i', '', $requestFile);

                    // Decode the base64 data
                    $decodedData = base64_decode($base64Data);

                    // Specify the file name
                    $fileName =  time().'-'.$request->fileName; // You can customize the file name if needed
                    $application->files=$fileName;
                    // Save the decoded data as a PDF file, overwriting the existing file if it exists
                    file_put_contents(public_path().'/uploads/'.$fileName, $decodedData);

                }


            }

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

            $message=new Message();
            $message->sender_id=$application->employee_id;
            $message->receiver_id=$application->reviewer_id;
            $message->application_id=$application->id;
            $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.' modified the application';
            $message->url="/leave/details/".$application->id;
            $message->created_at=Carbon::now();
            $message->save();

            $message=new Message();
            $message->sender_id=$application->employee_id;
            $message->receiver_id=$application->employee_id;
            $message->application_id=$application->id;
            $message->message=auth()->user()->name .' '.'('.auth()->user()->employee->designation->desg_nm.')'.' modified the application';
            $message->url="/leave/details/".$application->id;
            $message->created_at=Carbon::now();
            $message->save();


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
                        'sender.designation',
                        'sender.branch',
                        'sender.branch.company',
                        'sender.department',
                        'sender.user',
                        'receiver',
                        'receiver.designation',
                        'receiver.branch',
                        'receiver.branch.company',
                        'receiver.department',
                        'receiver.user'
                    ])
                ->where('application_id',$id)
                ->get();
        return $appHistory;
    }
    public function downloadAttachment($id){
        $application=Application::where('id',$id)->first();
        if(!$application){
            return response()->json(['message'=>'Application not found'],404);
        }
        if(!($application->files)){
            return response()->json(['message'=>'File not attach']);
        }
        else{
            $filePath = public_path('uploads/' . $application->files);
            return response()->download($filePath, $application->files, ['Content-Type' => 'application/pdf']);
        }
//        return $application;
    }

}
