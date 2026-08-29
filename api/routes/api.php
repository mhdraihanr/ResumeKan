<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CvController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/cvs', [CvController::class, 'index'])->name('cvs.index');
    Route::post('/cvs', [CvController::class, 'store'])->name('cvs.store');
    Route::get('/cvs/{cv}', [CvController::class, 'show'])->name('cvs.show');
    Route::put('/cvs/{cv}', [CvController::class, 'update'])->name('cvs.update');
    Route::delete('/cvs/{cv}', [CvController::class, 'destroy'])->name('cvs.destroy');
    Route::get('/cvs/{cv}/pdf', [CvController::class, 'pdf'])->name('cvs.pdf');

    Route::post('/ai/summary', [AiController::class, 'summary'])->middleware('throttle:5,1')->name('ai.summary');
});
