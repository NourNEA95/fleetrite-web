<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    
    // Tracking APIs
    Route::get('/tracking/objects', [TrackingController::class, 'getObjects']);
    
    // History APIs
    Route::get('/history/{imei}', [HistoryController::class, 'getRoute']);

    // Event APIs
    Route::get('/events', [EventController::class, 'getEvents']);
    Route::get('/events/latest', [EventController::class, 'getLatestEvents']);
    Route::delete('/events', [EventController::class, 'clearEvents']);

    // Dashboard APIs
    Route::get('/dashboard/unit-stats/{imei}', [DashboardController::class, 'getUnitStats']);

    // Object Settings APIs
    Route::get('/objects/{imei}/settings', [\App\Http\Controllers\Api\ObjectSettingsController::class, 'getObjectSettings']);
    Route::post('/objects/{imei}/settings', [\App\Http\Controllers\Api\ObjectSettingsController::class, 'updateObjectSettings']);

    // Report APIs
    Route::get('/reports', [\App\Http\Controllers\Api\ReportController::class, 'index']);
    Route::get('/reports/metadata', [\App\Http\Controllers\Api\ReportController::class, 'getMetadata']);
    Route::post('/reports/sensors', [\App\Http\Controllers\Api\ReportController::class, 'getSensors']);
    Route::post('/reports', [\App\Http\Controllers\Api\ReportController::class, 'store']);
    Route::delete('/reports/{id}', [\App\Http\Controllers\Api\ReportController::class, 'destroy']);
    Route::post('/reports/generate', [\App\Http\Controllers\Api\ReportController::class, 'generateReport']);
    Route::post('/reports/general-info/data', [\App\Http\Controllers\Api\ReportController::class, 'fetchGeneralInfoData']);
    
    // Modular Reports APIs
    Route::post('/reports/modular/general-information/generate', [\App\Http\Controllers\Api\Reports\GeneralInformationController::class, 'generate']);
    Route::post('/reports/modular/general-information/fetch', [\App\Http\Controllers\Api\Reports\GeneralInformationController::class, 'fetch']);

    Route::get('/reports/generated', [\App\Http\Controllers\Api\ReportController::class, 'indexGenerated']);
    Route::delete('/reports/generated/{id}', [\App\Http\Controllers\Api\ReportController::class, 'destroyGenerated']);
});
