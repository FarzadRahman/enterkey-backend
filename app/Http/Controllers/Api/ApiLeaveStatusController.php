<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiLeaveStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_status_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $leaveStatus = new LeaveStatus();
        $leaveStatus->leave_status_name=$request->leave_status_name;
        $leaveStatus->save();

        return response()->json(['message' => 'Leave Status created successfully', 'data' => $leaveStatus], 201);
    }
    public function update(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'leave_status_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
        $leaveStatus=LeaveStatus::find($id);
        if(!$leaveStatus){
            return response()->json(['message'=>'Leave Status not found'],404);
        }

        $leaveStatus->leave_status_name=$request->leave_status_name;
        $leaveStatus->save();

        return response()->json(['message' => 'Leave Status Updated successfully', 'data' => $leaveStatus], 201);
    }
    public function destroy($id){
        $leaveStatus=LeaveStatus::find($id);
        if(!$leaveStatus){
            return response()->json(['message'=>'Leave Status is not found'],404);
        }
        $leaveStatus->delete();
        return response()->json(['message'=>'Leave Status deleted Successfully'],200);
    }
    public function getAll(){
        $leaveStatus=LeaveStatus::get();
        return $leaveStatus;
    }

}
