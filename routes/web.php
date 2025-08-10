<?php

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

// Routes pour l'inventaire
Route::get('inventaire', [InventaireController::class, 'index'])->name('inventaire.index');
Route::post('inventaire/{inventaireItem}/equip', [InventaireController::class, 'equip'])->name('inventaire.equip');
Route::post('inventaire/{inventaireItem}/unequip', [InventaireController::class, 'unequip'])->name('inventaire.unequip');
Route::delete('inventaire/{inventaireItem}', [InventaireController::class, 'destroy'])->name('inventaire.destroy');

// Routes pour les boutiques
Route::get('boutiques', [BoutiqueController::class, 'index'])->name('boutiques.index');
Route::get('boutiques/{boutique}', [BoutiqueController::class, 'show'])->name('boutiques.show');
Route::post('boutiques/{boutique}/items/{boutiqueItem}/purchase', [BoutiqueController::class, 'purchase'])->name('boutiques.purchase');
Route::post('boutiques/{boutique}/sell', [BoutiqueController::class, 'sell'])->name('boutiques.sell');
Route::get('boutiques/{boutique}/items/{boutiqueItem}/price', [BoutiqueController::class, 'getPrice'])->name('boutiques.price');
Route::get('boutiques/{boutique}/sellable-items', [BoutiqueController::class, 'sellableItems'])->name('boutiques.sellable-items');
Route::get('boutiques/history', [BoutiqueController::class, 'history'])->name('boutiques.history');
