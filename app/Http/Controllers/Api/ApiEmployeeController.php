<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
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
            'email_address' => 'required|email|max:255',
            'office_id' => 'required',
            'branch_id' => 'required|integer',
//            'user_id' => 'required|integer',
            'designation_id' => 'required|integer',
            'department_id' => 'required|integer',
            'signature' => 'nullable|string',
            'password'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();
        $user=new User();
        $user->name=$request->full_name;
        $user->email=$request->email_address;
        $user->password=Hash::make($request->password);
        if($request->company){
            $user->company=$request->company;
        }
        else{
            $user->company=0;
        }
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
        $employee->signature = $request->signature;

        $employee->save();

        return response()->json(['message' => 'Employee created successfully', 'data' => $employee,'user'=>$user], 201);
    }
    public function update(Request $request, $id)
    {
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
        $employee->user_id = $request->user_id;
        $employee->designation_id = $request->designation_id;
        $employee->department_id = $request->department_id;
        $employee->signature = $request->signature;

        $employee->save();

        return response()->json(['message' => 'Employee updated successfully', 'data' => $employee], 200);
    }
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully'], 200);
    }
    public function getAll(){
//        $employee=Employee::get();
  //      return $employee;
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $employees = Employee::with(['designation', 'branch', 'department'])->paginate(10);
        return $employees;
    }
    public function resetPassword(Request $request){
        $validator=Validator::make($request->all(),[
            'password'=>'required|string',
        ]);
        if($validator->fails()){
            return response()->json($validator->errors(),400);
        }
        $user=User::find($request->email);
        if (!$user){
            return response()->json(['message'=>'No user found'],404);
        }
        $user->password=Hash::make($request->password);
        $user->save();

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

        return response()->json([
            'message' => 'Profile updated successfully',
            'employee' => $employee
        ], 200);
    }


}
