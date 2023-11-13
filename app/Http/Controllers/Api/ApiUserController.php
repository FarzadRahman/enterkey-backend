<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ApiUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function getAll()
    {
        $user= User::leftJoin('company', 'users.company', '=', 'company.comp_id')
            ->leftJoin('roles','roles.role_id','users.role_id')
            ->select('users.*', 'company.*','roles.role_name')
            ->paginate(10);;
        return response()->json(
            [
                'users'=>$user,
                'status' => true,
            ],200);
    }
}
