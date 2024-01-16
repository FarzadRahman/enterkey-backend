<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Branch;

class ApiBranchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'branch_name' => 'required|string|max:255',
//            'company_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $branch = new Branch();
        $branch->branch_name=$request->branch_name;
        $branch->company_id=auth()->user()->company;

        $branch->save();

        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($branch)
            ->withProperties($branch)
            ->log(auth()->user()->name . ' created branch');

        return response()->json(['message' => 'Branch created successfully', 'data' => $branch], 201);
    }
    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'branch_name' => 'required|string|max:255',
//            'company_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
        $branch=Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }
        $branch->branch_name=$request->branch_name;
        $branch->company_id=auth()->user()->company;

        $branch->save();
        activity('update')
            ->causedBy(auth()->user()->id)
            ->performedOn($branch)
            ->withProperties($branch)
            ->log(auth()->user()->name . ' updated branch');
        return response()->json(['message' => 'Branch Updated successfully', 'data' => $branch], 201);
    }
    public function getAll(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $branches=Branch::select('*')->with('company');
        if( auth()->user()->role_id==2){
            $branches=$branches->where('company_id',auth()->user()->company);
        }
        elseif (auth()->user()->role_id>2){
            return response(['message' => 'Access Forbidden'],403);
        }
        $branches=$branches->paginate(10);
        return $branches;
    }
    public function destroy($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }
        $employee=Employee::where('branch_id',$id)->count();
        if($employee>0){

            return response()->json(['message' => 'Branch can not be deleted'], 403);
        }
        $branch->delete();
        activity('delete')
            ->causedBy(auth()->user()->id)
            ->performedOn($branch)
            ->withProperties($branch)
            ->log(auth()->user()->name . ' deleted branch');
        return response()->json(['message' => 'Branch deleted successfully'], 200);
    }
}
