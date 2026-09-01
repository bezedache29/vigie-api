<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DigestController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\UserPreferenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('sources', SourceController::class);
    Route::apiResource('items', ItemController::class)->only(['index', 'show', 'update']);
    Route::apiResource('summaries', SummaryController::class)->only(['index', 'show']);
    Route::apiResource('digests', DigestController::class)->only(['index', 'show']);

    Route::get('/preferences', [UserPreferenceController::class, 'show']);
    Route::put('/preferences', [UserPreferenceController::class, 'update']);
});
