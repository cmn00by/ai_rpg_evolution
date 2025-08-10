<?php

use App\Http\Controllers\Api\CharacterStatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Routes pour les statistiques des personnages
Route::middleware('auth:sanctum')->group(function () {
    // Récupérer toutes les statistiques d'un personnage
    Route::get('/characters/{personnage}/stats', [CharacterStatsController::class, 'getCharacterStats']);
    
    // Récupérer une statistique spécifique d'un personnage
    Route::get('/characters/{personnage}/stats/{attribute}', [CharacterStatsController::class, 'getSpecificStat']);
    
    // Forcer le recalcul des statistiques d'un personnage
    Route::post('/characters/{personnage}/stats/recalculate', [CharacterStatsController::class, 'recalculateStats']);
    
    // Comparer les statistiques de plusieurs personnages
    Route::post('/characters/compare', [CharacterStatsController::class, 'compareCharacters']);
    
    // Récupérer l'historique des modifications (à implémenter)
    Route::get('/characters/{personnage}/stats/history', [CharacterStatsController::class, 'getStatsHistory']);
    
    // Routes administrateur
    Route::prefix('admin')->group(function () {
        // Statistiques du cache
        Route::get('/cache/stats', [CharacterStatsController::class, 'getCacheStats']);
    });
});
