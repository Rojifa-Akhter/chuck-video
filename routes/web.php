<?php

use App\Http\Controllers\ChunkVideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('upload',[ChunkVideoController::class,'create']);
Route::get('videos/{id}', [ChunkVideoController::class, 'show']);
