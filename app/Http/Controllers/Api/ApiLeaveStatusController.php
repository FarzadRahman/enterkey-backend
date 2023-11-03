<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiLeaveStatusController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_status_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $leaveStatus = LeaveStatus::create($data);

        return response()->json(['message' => 'Leave Status created successfully', 'data' => $leaveStatus], 201);
    }

}
