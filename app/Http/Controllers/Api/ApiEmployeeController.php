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
            'office_id' => 'required',
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
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'string|max:255',
            'gender' => 'string|max:10',
            'phone_number' => 'string|max:20',
            'email_address' => 'email|max:255',
            'office_id' => 'string',
            'branch_id' => 'integer',
            'user_id' => 'integer',
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

        $updatedEmployee=$employee->update($data);

        return response()->json(['message' => 'Employee updated successfully', 'data' => $updatedEmployee], 200);
    }
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully'], 204);
    }
    public function getAll(){
        $employee=Employee::get();
        return $employee;
    }
}
