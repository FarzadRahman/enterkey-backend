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
    Route::post('/employees/create',[ApiEmployeeController::class,'store']);

    Route::post('/logout',[AuthController::class,'logout']);

    Route::get('/employees',[ApiEmployeeController::class,'getAll']);
    Route::post('/employees/create',[ApiEmployeeController::class,'store']);
    Route::post('/employees/update/{id}',[ApiEmployeeController::class,'update']);
    Route::post('/employees/{id}',[ApiEmployeeController::class,'destroy']);


//    Route::post('/company',[ApiCompanyController::class,'store']);
    Route::post('/company/create',[ApiCompanyController::class,'store']);
    Route::post('/company/update/{id}',[ApiCompanyController::class,'update']);
    Route::post('/company/delete/{id}',[ApiCompanyController::class,'destroy']);
    Route::get('/company/{id}',[ApiCompanyController::class,'edit']);
    Route::get('/companies',[ApiCompanyController::class,'getAll']);

    Route::post('/branch/create',[ApiBranchController::class,'store']);
    Route::post('/branch/update/{id}',[ApiBranchController::class,'update']);
    Route::post('/branch/{id}',[ApiBranchController::class,'destroy']);
    Route::get('/branches',[ApiBranchController::class,'getAll']);


    Route::post('/department/create',[ApiDepartmentController::class,'store']);
    Route::post('/department/update/{id}',[ApiDepartmentController::class,'update']);
    Route::post('/department/{id}',[ApiDepartmentController::class,'destroy']);
    Route::get('/departments',[ApiDepartmentController::class,'getAll']);

    Route::post('/grade/create',[ApiGradeController::class,'store']);
    Route::post('/grade/update/{id}',[ApiGradeController::class,'update']);
    Route::post('/grade/{id}',[ApiGradeController::class,'destroy']);
    Route::get('/grades',[ApiGradeController::class,'getAll']);


    Route::post('/designation/create',[ApiDesignationController::class,'store']);
    Route::post('/designation/update/{id}',[ApiDesignationController::class,'update']);
    Route::post('/designation/{id}',[ApiDesignationController::class,'destroy']);
    Route::get('/designations',[ApiDesignationController::class,'getAll']);

    Route::post('/leave-status/create',[ApiLeaveStatusController::class,'store']);
    Route::post('/leave-status/update/{id}',[ApiLeaveStatusController::class,'update']);
    Route::post('/leave-status/{id}',[ApiLeaveStatusController::class,'destroy']);
    Route::get('/leave-status',[ApiLeaveStatusController::class,'getAll']);

    Route::post('/leave-type/create',[ApiLeaveTypeController::class,'store']);
    Route::post('/leave-type/update/{id}',[ApiLeaveTypeController::class,'update']);
    Route::post('/leave-type/{id]',[ApiLeaveTypeController::class,'destroy']);
    Route::get('/leave-type',[ApiLeaveTypeController::class,'getAll']);

    Route::post('/role/create',[ApiRoleController::class,'store']);
    Route::post('/role/update/{id}',[ApiRoleController::class,'update']);
    Route::post('/role/{id}',[ApiRoleController::class,'destroy']);
    Route::get('/role',[ApiRoleController::class,'getAll']);


});
