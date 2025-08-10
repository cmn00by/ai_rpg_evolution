<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Routes de gestion des personnages
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/character/select', [CharacterController::class, 'select'])->name('character.select');
    Route::get('/character/create', [CharacterController::class, 'create'])->name('character.create');
    Route::post('/character', [CharacterController::class, 'store'])->name('character.store');
    Route::post('/character/{character}/set-active', [CharacterController::class, 'setActive'])->name('character.set-active');
    Route::get('/character/{character}', [CharacterController::class, 'show'])->name('character.show');
});

// Routes pour l'espace joueur
Route::prefix('player')->middleware(['auth', 'verified', 'role:player|staff|admin'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Player\PlayerDashboardController::class, 'index'])->name('player.dashboard');
    Route::get('/dashboard/top-stats', [App\Http\Controllers\Player\PlayerDashboardController::class, 'getTopStats'])->name('player.dashboard.top-stats');
    Route::get('/dashboard/equipped-items', [App\Http\Controllers\Player\PlayerDashboardController::class, 'getEquippedItems'])->name('player.dashboard.equipped-items');
    Route::get('/dashboard/inventory-summary', [App\Http\Controllers\Player\PlayerDashboardController::class, 'getInventorySummary'])->name('player.dashboard.inventory-summary');
    Route::get('/dashboard/recent-events', [App\Http\Controllers\Player\PlayerDashboardController::class, 'getRecentEvents'])->name('player.dashboard.recent-events');
    Route::post('/dashboard/switch-character', [App\Http\Controllers\Player\PlayerDashboardController::class, 'switchCharacter'])->name('player.dashboard.switch-character');
    
    Route::get('/inventory', [App\Http\Controllers\Player\PlayerInventoryController::class, 'index'])->name('player.inventory');
    Route::post('/inventory/equip', [App\Http\Controllers\Player\PlayerInventoryController::class, 'equip'])->name('player.inventory.equip');
    Route::post('/inventory/unequip', [App\Http\Controllers\Player\PlayerInventoryController::class, 'unequip'])->name('player.inventory.unequip');
    Route::post('/inventory/repair', [App\Http\Controllers\Player\PlayerInventoryController::class, 'repair'])->name('player.inventory.repair');
    Route::get('/inventory/{item}', [App\Http\Controllers\Player\PlayerInventoryController::class, 'show'])->name('player.inventory.show');
    
    Route::get('/shops', [App\Http\Controllers\Player\PlayerShopController::class, 'index'])->name('player.shops');
    Route::get('/shops/{boutique}', [App\Http\Controllers\Player\PlayerShopController::class, 'show'])->name('player.shops.show');
    Route::post('/shops/{boutique}/buy', [App\Http\Controllers\Player\PlayerShopController::class, 'buy'])->name('player.shops.buy');
    Route::post('/shops/{boutique}/sell', [App\Http\Controllers\Player\PlayerShopController::class, 'sell'])->name('player.shops.sell');
    
    Route::get('/history', [App\Http\Controllers\Player\PlayerHistoryController::class, 'index'])->name('player.history');
    Route::get('/history/{transaction}', [App\Http\Controllers\Player\PlayerHistoryController::class, 'show'])->name('player.history.show');
    Route::get('/history/stats/overview', [App\Http\Controllers\Player\PlayerHistoryController::class, 'getHistoryStats'])->name('player.history.stats');
    Route::get('/history/export/csv', [App\Http\Controllers\Player\PlayerHistoryController::class, 'export'])->name('player.history.export');
});

// Routes d'administration
Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/characters', [AdminController::class, 'characters'])->name('characters');
    Route::get('/shops', [AdminController::class, 'shops'])->name('shops');
    Route::get('/purchases', [AdminController::class, 'purchases'])->name('purchases');
});

require __DIR__.'/auth.php';
