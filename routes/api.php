<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/save-contact', [UserController::class, 'saveContact']);
Route::post('/getUser', [UserController::class, 'getUser']);

Route::post('/login', [UserController::class, 'login']);
Route::post('/signup', [UserController::class, 'signup']);
Route::post('/insertPet', [UserController::class, 'insertPet'])->middleware('auth:sanctum');
Route::post('/selectPets', [UserController::class, 'selectPets'])->middleware('auth:sanctum');
	

    Route::post('/insertRoutine', [UserController::class, 'insertRoutine'])->middleware('auth:sanctum');
    Route::post('/selectRoutine', [UserController::class, 'selectRoutine'])->middleware('auth:sanctum');
    Route::post('/updateRoutine', [UserController::class, 'updateRoutine'])->middleware('auth:sanctum');
    Route::post('/deleteRoutine', [UserController::class, 'deleteRoutine'])->middleware('auth:sanctum');


    Route::post('/todayRoutine', [UserController::class,'todayRoutine'])->middleware('auth:sanctum');
    Route::post('/upcomingRoutine', [UserController::class,'upcomingRoutine'])->middleware('auth:sanctum');