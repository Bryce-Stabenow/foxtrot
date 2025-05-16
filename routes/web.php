<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [DashboardController::class, 'home'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
