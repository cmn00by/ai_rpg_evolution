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

// Routes joueur (nécessitent un personnage actif)
Route::middleware(['auth', 'verified', 'active-character'])->prefix('player')->name('player.')->group(function () {
    // Routes des boutiques, inventaire, etc. seront ajoutées ici
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
