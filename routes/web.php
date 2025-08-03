<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\InvitationAcceptanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'home'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    // Teams routes
    Route::prefix('teams')->name('teams.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/create', [TeamController::class, 'create'])->name('create');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{team}', [TeamController::class, 'show'])->name('show');
    });

    // Organization invitations (admin only)
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/', [OrganizationInvitationController::class, 'index'])->name('index');
        Route::post('/', [OrganizationInvitationController::class, 'store'])->name('store');
        Route::post('/{invitation}/resend', [OrganizationInvitationController::class, 'resend'])->name('resend');
        Route::delete('/{invitation}', [OrganizationInvitationController::class, 'destroy'])->name('destroy');
    });
});

// Public invitation acceptance
Route::prefix('invitations')->name('invitations.')->group(function () {
    Route::get('/{token}/accept', [InvitationAcceptanceController::class, 'show'])->name('accept');
    Route::post('/{token}/accept', [InvitationAcceptanceController::class, 'store'])->name('accept.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
