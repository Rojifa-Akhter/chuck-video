<?php

use App\Http\Controllers\ChunkVideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('upload',[ChunkVideoController::class,'create']);
Route::get('videos/{id}', [ChunkVideoController::class, 'show']);
