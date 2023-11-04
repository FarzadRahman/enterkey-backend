<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Branch;

class ApiBranchController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_name' => 'required|string|max:255',
            'company_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $branch = Branch::create($data);

        return response()->json(['message' => 'Branch created successfully', 'data' => $branch], 201);
    }
    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'branch_name' => 'string|max:255',
            'company_id' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
        $branch=Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }
        $branch= $branch->update($data);

        return response()->json(['message' => 'Branch Updated successfully', 'data' => $branch], 201);
    }
    public function getAll(){
        $branches=Branch::get();
        return $branches;
    }
    public function destroy($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        $branch->delete();

        return response()->json(['message' => 'Branch deleted successfully'], 200);
    }
}
