<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiDesignationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
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

        $designation= new Designation();
        $designation->desg_nm=$request->desg_nm;
        $designation->grade_id=$request->grade_id;
        $designation->save();
        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($designation)
            ->withProperties($designation)
            ->log(auth()->user()->name . ' created designation');
        return response()->json(['message' => 'Designation created successfully', 'data' => $designation], 201);
    }
    public function update(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'desg_nm' => 'required|string|max:255',
            'grade_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
        $designation=Designation::find($id);
        if(!$designation){
            return response()->json(['message'=>'Designation not found'],404);

        }
        $designation->desg_nm=$request->desg_nm;
        $designation->grade_id=$request->grade_id;
        $designation->save();
        activity('update')
            ->causedBy(auth()->user()->id)
            ->performedOn($designation)
            ->withProperties($designation)
            ->log(auth()->user()->name . ' updated designation');
        return response()->json(['message' => 'Designation updated successfully', 'data' => $designation], 201);
    }
    public function destroy($id){
        $designation=Designation::find($id);
        if (!$designation){
            return response()->json(['message'=>'Designation not found'],404);
        }
        $employee=Employee::where('designation_id',$id)->count();
        if($employee>0){
            return response()->json(['message'=>'Designation can not be deleted'],403);
        }
        $designation->delete();
        activity('destroy')
            ->causedBy(auth()->user()->id)
            ->performedOn($designation)
            ->withProperties($designation)
            ->log(auth()->user()->name . ' deleted designation');
        return response()->json(['message'=>'Designation deleted successfully'],200);
    }
    public function getAll(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $designation=Designation::with('grade')->paginate(10);
        return $designation;
    }
}
