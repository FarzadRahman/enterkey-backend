<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiEmployeeController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'gender' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20',
            'email_address' => 'required|email|max:255',
            'office_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'user_id' => 'required|integer',
            'designation_id' => 'required|integer',
            'department_id' => 'required|integer',
            'signature' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();

        $employee = Employee::create($data);

        return response()->json(['message' => 'Employee created successfully', 'data' => $employee], 201);
    }
}
