<?php

use App\Http\Controllers\AiQueryProcessController;
use Illuminate\Support\Facades\Route;


Route::get('/', [AiQueryProcessController::class, 'viewpage']);
Route::post('/ai-query-process', [AiQueryProcessController::class, 'convertToSQL'])->name('ai-query-process');
Route::get('/ai-query', [AiQueryProcessController::class, 'viewpage'])->name('ai-query');
