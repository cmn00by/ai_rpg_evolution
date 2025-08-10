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
    
    // Routes admin protégées
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/cache/stats', [CharacterStatsController::class, 'getCacheStats']);
    });
});

// Routes API pour l'espace joueur
Route::middleware(['auth:sanctum', 'role:player|staff|admin'])->prefix('me')->group(function () {
    // Informations du personnage actif
    Route::get('/character', [App\Http\Controllers\Api\PlayerApiController::class, 'getCharacter']);
    
    // Inventaire
    Route::get('/inventory', [App\Http\Controllers\Api\PlayerApiController::class, 'getInventory']);
    Route::post('/inventory/equip', [App\Http\Controllers\Api\PlayerApiController::class, 'equipItem'])
        ->middleware('throttle:equip');
    Route::post('/inventory/unequip', [App\Http\Controllers\Api\PlayerApiController::class, 'unequipItem'])
        ->middleware('throttle:equip');
});

// Routes API pour les boutiques
Route::middleware(['auth:sanctum', 'role:player|staff|admin'])->group(function () {
    Route::get('/shops', [App\Http\Controllers\Api\PlayerApiController::class, 'getShops']);
    Route::get('/shops/{boutique}', [App\Http\Controllers\Api\PlayerApiController::class, 'getShopCatalog']);
    Route::post('/shops/{boutique}/buy/{item}', [App\Http\Controllers\Api\PlayerApiController::class, 'buyItem'])
        ->middleware('throttle:purchase');
});
