<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiGradeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
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
        $grade = new Grade();
        $grade->grade_name=$request->grade_name;
        $grade->save();
        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($grade)
            ->withProperties($grade)
            ->log(auth()->user()->name . ' created grade');
        return response()->json(['message' => 'Grade created successfully', 'data' => $grade], 200);
    }
    public function update(Request $request,$id)
    {

        $validator = Validator::make($request->all(), [
            'grade_name' => 'required|string|max:255',
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
        $grade->grade_name=$request->grade_name;
        $grade->save();
        activity('updated')
            ->causedBy(auth()->user()->id)
            ->performedOn($grade)
            ->withProperties($grade)
            ->log(auth()->user()->name . ' updated grade');
        return response()->json(['message' => 'Grade Updated successfully', 'data' => $grade], 200);
    }
    public function destroy($id){
        $grade=Grade::find($id);
        if(!$grade){
            return response()->json(['message'=>'Grade is not found'],404);
        }
        $designation=Designation::where('grade_id',$id)->count();
        if($designation>0){
            return response()->json(['message'=>'Designation can not be deleted'],403);
        }
        $grade->delete();
        activity('delete')
            ->causedBy(auth()->user()->id)
            ->performedOn($grade)
            ->withProperties($grade)
            ->log(auth()->user()->name . ' deleted grade');
        return response()->json(['message'=>'Grade deleted successfully'],200);
    }
    public function getAll(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $grade=Grade::paginate(10);
        return $grade;
    }
}
