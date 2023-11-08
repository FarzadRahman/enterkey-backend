<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ApiUserController extends Controller
{
    public function getAll()
    {
        $user= User::leftJoin('company', 'users.company', '=', 'company.comp_id')
            ->select('users.*', 'company.*')
            ->paginate(10);;
        return response()->json(
            [
                'users'=>$user,
                'status' => true,
            ],200);
    }
}
