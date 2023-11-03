<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiDesignationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'desg_nm' => 'required|string|max:255',
            'grade_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $designation= Designation::create($data);

        return response()->json(['message' => 'Designation created successfully', 'data' => $designation], 201);
    }
}
