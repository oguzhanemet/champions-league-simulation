<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SimulationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('simulation')->group(function () {
    Route::get('/standings', [SimulationController::class, 'getStandings']);
    Route::get('/fixtures', [SimulationController::class, 'getFixtures']);
    Route::get('/predictions', [SimulationController::class, 'getPredictions']);
    
    Route::post('/generate-fixtures', [SimulationController::class, 'generateFixtures']);
    Route::post('/play-next-week', [SimulationController::class, 'playNextWeek']);
    Route::post('/play-all', [SimulationController::class, 'playAll']);
    Route::post('/reset', [SimulationController::class, 'resetData']);
    Route::post('/add-team', [\App\Http\Controllers\Api\SimulationController::class, 'addTeam']);
    Route::delete('/remove-team/{id}', [\App\Http\Controllers\Api\SimulationController::class, 'removeTeam']);
    Route::post('/update-score/{id}', [\App\Http\Controllers\Api\SimulationController::class, 'updateScore']);

});