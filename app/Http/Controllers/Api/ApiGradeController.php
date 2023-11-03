<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiGradeController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grade_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $grade = Grade::create($data);

        return response()->json(['message' => 'Grade created successfully', 'data' => $grade], 201);
    }
}
