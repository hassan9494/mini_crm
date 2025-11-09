<?php

use App\Domains\Clients\Controllers\ClientController;
use App\Domains\Communications\Controllers\CommunicationController;
use App\Domains\Dashboard\Controllers\DashboardController;
use App\Domains\FollowUps\Controllers\FollowUpController;
use App\Domains\Users\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Public routes
    Route::post('auth/login', [AuthController::class, 'login']);

    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function (): void {
        // Auth routes
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Dashboard (admin and manager only)
        Route::get('dashboard', DashboardController::class)
            ->middleware('role:admin,manager');

        // Client management routes
        Route::middleware('role:admin,manager,sales_rep')->group(function (): void {
            Route::get('clients', [ClientController::class, 'index']);
            Route::get('clients/{client}', [ClientController::class, 'show']);
            Route::put('clients/{client}', [ClientController::class, 'update']);
            
            // Admin and manager only
            Route::post('clients', [ClientController::class, 'store'])->middleware('role:admin,manager');
            Route::delete('clients/{client}', [ClientController::class, 'destroy'])->middleware('role:admin,manager');

            // Communications (nested under clients)
            Route::get('clients/{client}/communications', [CommunicationController::class, 'index']);
            Route::post('clients/{client}/communications', [CommunicationController::class, 'store']);
            Route::get('clients/{client}/communications/{communication}', [CommunicationController::class, 'show']);
            Route::put('clients/{client}/communications/{communication}', [CommunicationController::class, 'update']);
            Route::delete('clients/{client}/communications/{communication}', [CommunicationController::class, 'destroy']);

            // Follow-ups (nested under clients)
            Route::get('clients/{client}/follow-ups', [FollowUpController::class, 'index']);
            Route::post('clients/{client}/follow-ups', [FollowUpController::class, 'store']);
            Route::get('clients/{client}/follow-ups/{follow_up}', [FollowUpController::class, 'show']);
            Route::put('clients/{client}/follow-ups/{follow_up}', [FollowUpController::class, 'update']);
            Route::delete('clients/{client}/follow-ups/{follow_up}', [FollowUpController::class, 'destroy']);
        });
    });
});
