<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiEmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'gender' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20',
//            'email_address' => 'required|email|max:255',
//            'office_id' => 'required',
            'branch_id' => 'required|integer',
//            'user_id' => 'required|integer',
            'designation_id' => 'required|integer',
            'department_id' => 'required|integer',
            'signature' => 'nullable|string',
//            'password'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }


        $data = $validator->validated();
        $user=new User();
        $user->name=$request->full_name;
        $user->email=$request->email_address;
        if($request->password){
            $user->password=Hash::make($request->password);
        }
        else{
            $user->password='$2y$10$Fwu9qNdKCuQFCbrlDIkY4.6bpLTyTvGXzoc3/dUd5NsSIJSGmmZma';
        }
        $user->company=auth()->user()->company;
        $user->role_id = 3;
        $user->phone = $request->phone_number;
        $user->save();

        $employee=new Employee();
        $employee->full_name = $request->full_name;
        $employee->gender = $request->gender;
        $employee->phone_number = $request->phone_number;
        $employee->email_address = $request->email_address;
        $employee->office_id = $request->office_id;
        $employee->branch_id = $request->branch_id;
        $employee->user_id = $user->id;
        $employee->designation_id = $request->designation_id;
        $employee->department_id = $request->department_id;

        if(isset($request->isApprover) && $request->isApprover){
            $employee->isApprover=1;
        }
        else{
            $employee->isApprover=0;
        }
        if(isset($request->isRecorrder) && $request->isRecorrder){
            $employee->isRecorder=1;
        }
        else{
            $employee->isRecorder=0;
        }


        $employee->signature = $request->signature;

        $employee->save();
        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($employee)
            ->withProperties($employee)
            ->log(auth()->user()->name . ' created employee');
        return response()->json(['message' => 'Employee created successfully', 'data' => $employee,'user'=>$user], 201);
    }
    public function update(Request $request, $id)
    {
//        return $request->all();
        $validator = Validator::make($request->all(), [
            'full_name' => 'string|max:255',
            'gender' => 'string|max:10',
            'phone_number' => 'string|max:20',
            'email_address' => 'email|max:255',
            'office_id' => 'string',
            'branch_id' => 'required|integer',
//            'user_id' => 'integer',
            'designation_id' => 'integer',
            'department_id' => 'integer',
            'signature' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();

        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->full_name = $request->full_name;
        $employee->gender = $request->gender;
        $employee->phone_number = $request->phone_number;
        $employee->email_address = $request->email_address;
        $employee->office_id = $request->office_id;
        $employee->branch_id = $request->branch_id;
        $employee->designation_id = $request->designation_id;
        $employee->department_id = $request->department_id;
        if($request->isApprover){
            $employee->isApprover=1;
        }
        else{
            $employee->isApprover=0;
        }
        if($request->isRecorrder){
            $employee->isRecorder=1;
        }
        else{
            $employee->isRecorder=0;
        }
        $employee->save();
        activity('update')
            ->causedBy(auth()->user()->id)
            ->performedOn($employee)
            ->withProperties($employee)
            ->log(auth()->user()->name . ' update employee');
        return response()->json(['message' => 'Employee updated successfully ', 'data' => $employee], 200);
    }
    public function destroy($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
        $user=User::where('id',$employee->user_id)->first();
        Employee::where('emp_id',$id)->delete();
        User::where('id',$employee->user_id)->delete();

        activity('destroy')
            ->causedBy(auth()->user()->id)
            ->performedOn($employee)
            ->withProperties($employee)
            ->log(auth()->user()->name . ' deleted employee');

        return response()->json(['message' => 'Employee deleted successfully'], 200);
    }
    public function getAll(Request $r){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }

        $employees = Employee::with(['designation', 'branch', 'department'])
            ->leftJoin('users', 'users.id', 'employee.user_id')
            ->join('branch', 'branch.bran_id', 'employee.branch_id')
            ->join('designation', 'designation.desg_id', 'employee.designation_id')
            ->join('department', 'department.dept_id', 'employee.department_id');

        if (auth()->user()->role != 1) {
            $employees = $employees->where('users.company', auth()->user()->company);
        }

        if ($r->searchQuery) {
            $employees = $employees->where(function ($query) use ($r) {
                $query->where('full_name', 'like', '%' . $r->searchQuery . '%')
                    ->orWhere('email_address', 'like', '%' . $r->searchQuery . '%')
                    ->orWhere('phone_number', 'like', '%' . $r->searchQuery . '%')
                    ->orWhere('office_id', 'like', '%' . $r->searchQuery . '%')
                    ->orWhere('branch.branch_name', 'like', '%' . $r->searchQuery . '%')
                    ->orWhere('designation.desg_nm', 'like', '%' . $r->searchQuery . '%')
                    ->orWhere('department.department_name', 'like', '%' . $r->searchQuery . '%')
                    ->orWhere(function ($q) use ($r) {
                        if ($r->searchQuery == 'Employee') {
                            $q->where('isApprover', 0)->where('isRecorder', 0);
                        } elseif ($r->searchQuery == 'Approver') {
                            $q->where('isApprover', 1)->where('isRecorder', 0);
                        } elseif ($r->searchQuery == 'Recorder') {
                            $q->where('isApprover', 0)->where('isRecorder', 1);
                        } elseif ($r->searchQuery == 'Approver , Recorder') {
                            $q->where('isApprover', 1)->where('isRecorder', 1);
                        }
                    });
            });
        }

        if ($r->isPaginate == "false") {
            $employees = $employees->get();
        } else {
            $employees = $employees->paginate(10);
        }

        return $employees;
    }

    public function resetPassword(Request $request){
        $validator=Validator::make($request->all(),[
            'password'=>'required|string',
        ]);
        if($validator->fails()){
            return response()->json($validator->errors(),400);
        }
        $user=User::where('email',$request->email)->first();
//        return $user."not found";
        if (!$user){
            return response()->json(['message'=>'No user found'],404);
        }
        $user->password=Hash::make($request->password);
        $user->save();
        activity('resetPass')
            ->causedBy(auth()->user()->id)
            ->performedOn($user)
            ->withProperties($user)
            ->log(auth()->user()->name . ' reset password');
        return response()->json(['message'=>'Password reset successfully'],200);
    }
    public function profile(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employee = Employee::where('user_id', auth()->user()->id)
            ->with(['designation', 'designation.grade', 'department', 'branch', 'branch.company'])
            ->first();
        return $employee;
    }
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'string',
            'phone_number' => 'string',
            'signature' => 'nullable|string', // Adjust validation for base64-encoded string
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $employee = Employee::where('user_id', auth()->user()->id)->first();
        $user = User::where('id', auth()->user()->id)->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        if ($request->full_name) {
            $employee->full_name = $request->full_name;
            $user->name = $request->full_name;
        }

        if ($request->phone_number) {
            $employee->phone_number = $request->phone_number;
            $user->phone = $request->phone_number;
        }

        if ($request->signature) {
            if ($employee->signature) {
                $previousSignaturePath = public_path('signature') . '/' . $employee->signature;
                if (file_exists($previousSignaturePath)) {
                    unlink($previousSignaturePath);
                }
            }

            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature));

            $imageName = "Employee-Signature-" . Str::random(10) . '.webp';

            Storage::disk('local')->put('public/signature/' . $imageName, $imageData);

            $employee->signature = $imageName;
        }

        $employee->save();
        $user->save();
        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($employee)
            ->withProperties($employee)
            ->log(auth()->user()->name . ' update profile');
        return response()->json([
            'message' => 'Profile updated successfully',
            'employee' => $employee
        ], 200);
    }
    public function totalLeave($id){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employee=Employee::where('emp_id',$id)->first();
        if (!$employee){
            return response()->json(['message'=>'Employee not found'],404);
        }
        $totalApprovedDays=Application::select('employee_id','approved_total_days')
            ->where('status',2)
            ->where('employee_id',$employee->emp_id)
            ->whereYear('end_date',Carbon::now())
            ->sum('approved_total_days');
        return response()->json(['totalApprovedLeave'=>$totalApprovedDays],200);
    }
    public function getEmployeeDetails($id){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employee =Employee::
        with(
                [
                    'designation', 'branch', 'department','user','branch.company'
                ]
             )
            ->where('emp_id',$id)
            ->first();
        return $employee;
    }
    public function employeeTotalCasualLeave($id){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employee=Employee::where('emp_id',$id)->first();
        if (!$employee){
            return response()->json(['message'=>'Employee not found'],404);
        }
        $totalApprovedDays=Application::select('employee_id','approved_total_days')
            ->where('status',2)
            ->where('leave_type',1)
            ->where('employee_id',$employee->emp_id)
            ->whereYear('end_date',Carbon::now())
            ->sum('approved_total_days');
        $remainingLeave=20-$totalApprovedDays;
        if($remainingLeave<0){
            return response()->json(['message'=>'invalid leave'],200);
        }

        return response()->json(['totalApprovedLeave'=>$totalApprovedDays,'remainingLeave'=>$remainingLeave],200);
    }

}
