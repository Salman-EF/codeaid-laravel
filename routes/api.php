<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TeamController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix'=>'player'], function () {
    $idInThePath = '/{id}';
    Route::get('/', [PlayerController::class, 'index']);
    Route::get($idInThePath, [PlayerController::class, 'show']);
    Route::post('/', [PlayerController::class, 'store']);
    Route::put($idInThePath, [PlayerController::class, 'update']);
    Route::delete($idInThePath, [PlayerController::class, 'destroy']);
});

Route::post('team/process', [TeamController::class, 'process']);
