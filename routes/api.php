<?php

use App\Http\Controllers\Api\ApiBranchController;
use App\Http\Controllers\Api\ApiCompanyController;
use App\Http\Controllers\Api\ApiDepartmentController;
use App\Http\Controllers\Api\ApiDesignationController;
use App\Http\Controllers\Api\ApiEmployeeController;
use App\Http\Controllers\Api\ApiGradeController;
use App\Http\Controllers\Api\ApiLeaveStatusController;
use App\Http\Controllers\Api\ApiLeaveTypeController;
use App\Http\Controllers\Api\ApiRoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::group(['middleware'=>'api'],function($routes){
    Route::post('/register',[AuthController::class,'register']);
    Route::post('/login',[AuthController::class,'login']);
    Route::post('/dashboard',[AuthController::class,'dashboard']);

    Route::post('/logout',[AuthController::class,'logout']);

    Route::post('/employees',[ApiEmployeeController::class,'store']);

    Route::post('/company',[ApiCompanyController::class,'store']);
    Route::post('/department',[ApiDepartmentController::class,'store']);
    Route::post('/grade',[ApiGradeController::class,'store']);
    Route::post('/designation',[ApiDesignationController::class,'store']);

    Route::post('/leave-status',[ApiLeaveStatusController::class,'store']);
    Route::post('/leave-type',[ApiLeaveTypeController::class,'store']);
    Route::post('/role',[ApiRoleController::class,'store']);

});
