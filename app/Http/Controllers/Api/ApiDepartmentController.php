<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiDepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $department =new Department();
        $department->department_name=$request->department_name;
        $department->save();
        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($department)
            ->withProperties($department)
            ->log(auth()->user()->name . ' created department');
        return response()->json(['message' => 'Department created successfully', 'data' => $department], 201);
    }
    public function update(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
        $department=Department::find($id);
        if(!$department){
            return response()->json(['message'=>'Department not found'],404);
        }
        $department->department_name=$request->department_name;
        $department->save();
        activity('update')
            ->causedBy(auth()->user()->id)
            ->performedOn($department)
            ->withProperties($department)
            ->log(auth()->user()->name . ' updated department');
        return response()->json(['message' => 'Department updated successfully', 'data' => $department], 201);
    }
    public function destroy($id){
        $department = Department::find($id);

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }
        $employee=Employee::where('department_id',$id)->count();
        if($employee>0){
            return response()->json(['message'=>'Department can not be deleted'],403);
        }
        $department->delete();
        activity('destroy')
            ->causedBy(auth()->user()->id)
            ->performedOn($department)
            ->withProperties($department)
            ->log(auth()->user()->name . ' deleted department');
        return response()->json(['message' => 'Department deleted successfully'], 200);

    }
    public function getAll(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $deparment=Department::paginate(10);
        return $deparment;
    }
}
