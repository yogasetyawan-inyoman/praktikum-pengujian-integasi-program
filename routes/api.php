<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::post('register',[AuthController::class,'register']);
Route::post('login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('logout',[AuthController::class,'logout']);
    Route::get('leave-requests',[LeaveRequestController::class,'index'])->middleware('role:pegawai,atasan,admin');
    Route::post('leave-requests',[LeaveRequestController::class,'store'])->middleware('role:pegawai,admin');
    Route::get('leave-requests/{id}',[LeaveRequestController::class,'show']);
    Route::put('leave-requests/{id}',[LeaveRequestController::class,'update']);
    Route::delete('leave-requests/{id}',[LeaveRequestController::class,'destroy']);
    Route::post('leave-requests/{id}/approve',[LeaveRequestController::class,'approve'])->middleware('role:atasan,admin');
    Route::post('leave-requests/{id}/reject',[LeaveRequestController::class,'reject'])->middleware('role:atasan,admin');
}); 