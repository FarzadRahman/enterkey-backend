<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function getAll(){
        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        if(auth()->user()->role_id>1){
            return response()->json(['message'=>'Access denied'],403);
        }
        $activityLog=ActivityLog::with(
                [
                    'user',
                    'user.employee.designation',
                    'user.employee.department',
                    'user.employee.branch',
                    'user.employee.branch.company'
                ])->get();
       return response()->json($activityLog);
    }
}
