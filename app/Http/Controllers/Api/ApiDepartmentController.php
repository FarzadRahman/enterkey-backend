<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiDepartmentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $department = Department::create($data);

        return response()->json(['message' => 'Department created successfully', 'data' => $department], 201);
    }
    public function update(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
        $department=Department::find($id);
        if(!$department){
            return response()->json(['message'=>'Department not found'],404);
        }
        $department = $department->update($data);

        return response()->json(['message' => 'Department created successfully', 'data' => $department], 201);
    }
    public function destroy($id){
        $department = Department::find($id);

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        $department->delete();

        return response()->json(['message' => 'Department deleted successfully'], 200);

    }
    public function getAll(){
        $deparment=Department::get();
        return $deparment;
    }
}
