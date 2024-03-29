<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiLeaveTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_type_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $leaveType =new LeaveType();
        $leaveType->leave_type_name=$request->leave_type_name;
        $leaveType->save();
        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($leaveType)
            ->withProperties($leaveType)
            ->log(auth()->user()->name . ' created leave type');
        return response()->json(['message' => 'Leave Type created successfully', 'data' => $leaveType], 201);
    }
    public function update(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'leave_type_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
        $leaveType=LeaveType::find($id);
        if(!$leaveType){
            return response()->json(['message'=>'Leave Type not found'],404);
        }

        $leaveType->leave_type_name=$request->leave_type_name;
        $leaveType->save();
        activity('update')
            ->causedBy(auth()->user()->id)
            ->performedOn($leaveType)
            ->withProperties($leaveType)
            ->log(auth()->user()->name . ' updated leave type');
        return response()->json(['message' => 'Leave Type updated successfully', 'data' => $leaveType], 201);
    }
    public function destroy($id){
        $leaveType=LeaveType::find($id);
        if(!$leaveType){
            return response()->json(['message'=>'Leave type not found'],404);
        }
        $leaveType->delete();
        activity('destroy')
            ->causedBy(auth()->user()->id)
            ->performedOn($leaveType)
            ->withProperties($leaveType)
            ->log(auth()->user()->name . ' delete leave type');
        return response()->json(['message'=>'Leave type deleted successfully'],200);
    }
    public function getAll(){
//        try {
//            $user = auth()->userOrFail();
//        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
//            return response(['message' => 'Login first'], 401);
//        }
        $leaveType=LeaveType::get();
        return $leaveType;
    }
}
