<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiRoleController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $role = Role::create($data);

        return response()->json(['message' => 'Role created successfully', 'data' => $role], 201);
    }
    public function update(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data
        $role=Role::find($id);
        if(!$role){
            return response()->json(['message'=>'Role not found'],404);
        }

        $role = $role->update($data);

        return response()->json(['message' => 'Role Updated successfully', 'data' => $role], 201);

    }
    public function destroy($id){
        $role=Role::find($id);
        if(!$role){
            return response()->json(['message'=>'Role not found'],404);
        }
        $role->delete();
        return response()->json(['message' => 'Role deleted successfully', 'data' => $role], 200);
    }
    public function getAll(){
        $role=Role::get();
        return $role;
    }
}
