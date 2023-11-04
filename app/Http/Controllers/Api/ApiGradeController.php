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
//        return $data;
        $grade = Grade::create($data);



        return response()->json(['message' => 'Grade created successfully', 'data' => $grade], 200);
    }
    public function update(Request $request,$id)
    {

        $validator = Validator::make($request->all(), [
            'grade_name' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
//        return $data;
        $grade=Grade::find($id);
        if (!$grade){
            return response()->json(['message'=>'Grade is not found'],404);
        }
        $grade = $grade->update($data);



        return response()->json(['message' => 'Grade Updated successfully', 'data' => $grade], 200);
    }
    public function destroy($id){
        $grade=Grade::find($id);
        if(!$grade){
            return response()->json(['message'=>'Grade is not found'],404);
        }
        $grade->delete();
        return response()->json(['message'=>'Grade deleted successfully'],200);
    }
    public function getAll(){
        $grade=Grade::get();
        return $grade;
    }
}
