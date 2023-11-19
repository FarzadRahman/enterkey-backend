<?php

use App\Http\Controllers\Api\ApiApplicationController;
use App\Http\Controllers\Api\ApiBranchController;
use App\Http\Controllers\Api\ApiCompanyController;
use App\Http\Controllers\Api\ApiDepartmentController;
use App\Http\Controllers\Api\ApiDesignationController;
use App\Http\Controllers\Api\ApiEmployeeController;
use App\Http\Controllers\Api\ApiGradeController;
use App\Http\Controllers\Api\ApiLeaveStatusController;
use App\Http\Controllers\Api\ApiLeaveTypeController;
use App\Http\Controllers\Api\ApiRoleController;
use App\Http\Controllers\Api\ApiUserController;
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
//    Route::get('/users',[AuthController::class,'users']);
    Route::post('/auth/change-password',[AuthController::class,'changePassword']);
    //Route::post('/employees/create',[ApiEmployeeController::class,'store']);

    Route::post('/logout',[AuthController::class,'logout']);

    Route::get('/users',[ApiUserController::class,'getAll']);
    Route::post('/upload/image',[ApiUserController::class,'uploadImage']);
    Route::post('/upload/sign',[ApiUserController::class,'uploadSign']);

    Route::get('/employees',[ApiEmployeeController::class,'getAll']);
//    Route::get('/employees/list',[ApiEmployeeController::class,'getAllEmployee']);
    Route::post('/employees/create',[ApiEmployeeController::class,'store']);
    Route::post('/employees/update/{id}',[ApiEmployeeController::class,'update']);
    Route::post('/employees/{id}',[ApiEmployeeController::class,'destroy']);
    Route::post('/employee/reset/password',[ApiEmployeeController::class,'resetPassword']);
    Route::get('/employee/profile',[ApiEmployeeController::class,'profile']);
    Route::post('/employee/update/profile',[ApiEmployeeController::class,'updateProfile']);

//    Route::post('/company',[ApiCompanyController::class,'store']);
    Route::post('/company/create',[ApiCompanyController::class,'store']);
    Route::post('/company/update/{id}',[ApiCompanyController::class,'update']);
    Route::post('/company/delete/{id}',[ApiCompanyController::class,'destroy']);
    Route::get('/company/{id}',[ApiCompanyController::class,'edit']);
    Route::get('/companies',[ApiCompanyController::class,'getAll']);

    Route::post('/branch/create',[ApiBranchController::class,'store']);
    Route::post('/branch/update/{id}',[ApiBranchController::class,'update']);
    Route::post('/branch/delete/{id}',[ApiBranchController::class,'destroy']);
    Route::get('/branches',[ApiBranchController::class,'getAll']);


    Route::post('/department/create',[ApiDepartmentController::class,'store']);
    Route::post('/department/update/{id}',[ApiDepartmentController::class,'update']);
    Route::post('/department/delete/{id}',[ApiDepartmentController::class,'destroy']);
    Route::get('/departments',[ApiDepartmentController::class,'getAll']);

    Route::post('/grade/create',[ApiGradeController::class,'store']);
    Route::post('/grade/update/{id}',[ApiGradeController::class,'update']);
    Route::post('/grade/delete/{id}',[ApiGradeController::class,'destroy']);
    Route::get('/grades',[ApiGradeController::class,'getAll']);


    Route::post('/designation/create',[ApiDesignationController::class,'store']);
    Route::post('/designation/update/{id}',[ApiDesignationController::class,'update']);
    Route::post('/designation/delete/{id}',[ApiDesignationController::class,'destroy']);
    Route::get('/designations',[ApiDesignationController::class,'getAll']);

    Route::post('/leave-status/create',[ApiLeaveStatusController::class,'store']);
    Route::post('/leave-status/update/{id}',[ApiLeaveStatusController::class,'update']);
    Route::post('/leave-status/delete/{id}',[ApiLeaveStatusController::class,'destroy']);
    Route::get('/leave-status',[ApiLeaveStatusController::class,'getAll']);

    Route::post('/leave-type/create',[ApiLeaveTypeController::class,'store']);
    Route::post('/leave-type/update/{id}',[ApiLeaveTypeController::class,'update']);
    Route::post('/leave-type/delete/{id}',[ApiLeaveTypeController::class,'destroy']);
    Route::get('/leave-type',[ApiLeaveTypeController::class,'getAll']);

    Route::post('/role/create',[ApiRoleController::class,'store']);
    Route::get('/role/edit/{id}',[ApiRoleController::class,'edit']);
    Route::post('/role/update/{id}',[ApiRoleController::class,'update']);
    Route::post('/role/delete/{id}',[ApiRoleController::class,'destroy']);
    Route::get('/role',[ApiRoleController::class,'getAll']);

    Route::post('/leave/create',[ApiApplicationController::class,'store']);

    Route::get('/leave/employee-list',[ApiApplicationController::class,'getLeaveEmployee']);
    Route::get('/leave/recorder-list',[ApiApplicationController::class,'getRecorderEmployee']);
    Route::get('/leave/application-list',[ApiApplicationController::class,'getApplicationList']);
    Route::get('/leave/own-application-list',[ApiApplicationController::class,'getOwnApplicationList']);
    Route::post('/leave/applied-list',[ApiApplicationController::class,'appliedList']);
    Route::post('/leave/application-approved/{id}',[ApiApplicationController::class,'applicationApproved']);
    Route::post('/leave/application-pass/{id}',[ApiApplicationController::class,'applicationPass']);
    Route::post('/leave/application/for-recorder',[ApiApplicationController::class,'getApplicationForRecorder']);
    Route::post('/leave/application/for-approver',[ApiApplicationController::class,'getApplicationForApprover']);
    Route::get('/leave/details/{id}',[ApiApplicationController::class,'getApplicationDetails']);
    Route::post('/leave/edit-application/{id}',[ApiApplicationController::class,'editApplicationDetails']);

});
