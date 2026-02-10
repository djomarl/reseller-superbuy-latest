<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\PresetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperbuyController; // <--- Toegevoegd
use Illuminate\Support\Facades\Route;

Route::get('/', function ()
{
    return redirect()->route('login');
});

// Beveiligde routes (vereist inloggen)
Route::middleware(['auth', 'verified'])->group(function ()
{
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/layout-toggle', [DashboardController::class, 'toggleLayout'])->name('dashboard.layout.toggle');

    // Voorraad (Inventory)
    Route::post('/inventory/import', [InventoryController::class, 'importText'])->name('inventory.import');
    Route::post('/inventory/reorder', [InventoryController::class, 'reorder'])->name('inventory.reorder');
    Route::post('/inventory/{item}/sold', [InventoryController::class, 'markAsSold'])->name('inventory.sold');
    Route::post('/inventory/bulk-action', [InventoryController::class, 'bulkAction'])->name('inventory.bulkAction');
    Route::get('/inventory/archive', [InventoryController::class, 'index'])->name('inventory.archive');
    Route::resource('inventory', InventoryController::class)->except(['show']);

    // Superbuy Integration (Web)
    Route::get('/superbuy', [SuperbuyController::class, 'index'])->name('superbuy.index');
    Route::post('/superbuy/fetch', [SuperbuyController::class, 'fetch'])->name('superbuy.fetch');
    Route::post('/superbuy/import', [SuperbuyController::class, 'import'])->name('superbuy.import');

    // Pakketten (Parcels)
    Route::resource('parcels', ParcelController::class)->except(['show']);

    // Templates (Presets)
    Route::resource('presets', PresetController::class)->except(['show']);

    // Profiel
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // NIEUW: Route voor het genereren van de secret
    Route::patch('/profile/secret', [ProfileController::class, 'generateSecret'])->name('profile.secret');

    // Rapport
    Route::get('/dashboard/report', [DashboardController::class, 'report'])->name('dashboard.report');

    // Live Check Route
    Route::get('/inventory/status', [InventoryController::class, 'checkStatus'])->name('inventory.status');
    });

require __DIR__ . '/auth.php';

// --- EXTENSIE ROUTE (BUITEN DE AUTH MIDDLEWARE) ---
// Dit is de fix voor de 404 error.
Route::post('/superbuy/import-extension', [SuperbuyController::class, 'importFromExtension'])
    ->name('superbuy.import_extension');

    // Check of items al bestaan (voor de extensie)
Route::post('/superbuy/check-items', [SuperbuyController::class, 'checkExistingItems'])
    ->name('superbuy.check');