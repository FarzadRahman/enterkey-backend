<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }

    public function store(Request $request){
        return $request;
    }
}
