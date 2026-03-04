<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Panel\AuthController;
use App\Http\Controllers\Panel\ContactController;
use App\Http\Controllers\Panel\ConversationController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\FlowController;
use App\Http\Controllers\Panel\MessageController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\UserController;
use App\Http\Controllers\Panel\TicketController;

// Panel APIs using 'web' middleware for sessions natively
Route::prefix('api')->group(function () {
    // Auth
    Route::post('/auth/login', [AuthController::class , 'login']);

    // Protected panel API routes using standard web login state
    Route::middleware('auth')->group(function () {
            Route::post('/auth/logout', [AuthController::class , 'logout']);
            Route::get('/auth/user', [AuthController::class , 'user']);

            // Tickets
            Route::get('/tickets', [TicketController::class , 'index']);
            Route::get('/tickets/{ticket}', [TicketController::class , 'show']);
            Route::put('/tickets/{ticket}', [TicketController::class , 'update']);

            // Dashboard
            Route::get('/dashboard/stats', [DashboardController::class , 'index']);

            // Conversations
            Route::get('/conversations', [ConversationController::class , 'index']);
            Route::get('/conversations/{conversation}', [ConversationController::class , 'show']);
            Route::post('/conversations/{conversation}/assign', [ConversationController::class , 'assignAgent']);
            Route::post('/conversations/{conversation}/take-over', [ConversationController::class , 'takeOver']);
            Route::post('/conversations/{conversation}/reactivate-bot', [ConversationController::class , 'reactivateBot']);
            Route::post('/conversations/{conversation}/close', [ConversationController::class , 'close']);

            // Messages
            Route::post('/conversations/{conversation}/messages', [MessageController::class , 'send']);
            Route::post('/conversations/{conversation}/read', [MessageController::class , 'markAsRead']);

            // Flows
            Route::get('/flows', [FlowController::class , 'index']);
            Route::post('/flows', [FlowController::class , 'store']);
            Route::get('/flows/{flow}', [FlowController::class , 'show']);
            Route::put('/flows/{flow}', [FlowController::class , 'update']);
            Route::delete('/flows/{flow}', [FlowController::class , 'destroy']);
            Route::post('/flows/{flow}/toggle', [FlowController::class , 'toggleActive']);

            // Contacts
            Route::get('/contacts', [ContactController::class , 'index']);
            Route::get('/contacts/{contact}', [ContactController::class , 'show']);
            Route::put('/contacts/{contact}', [ContactController::class , 'update']);

            // Settings
            Route::get('/settings', [SettingsController::class , 'index']);
            Route::put('/settings', [SettingsController::class , 'update']);
            Route::post('/settings/toggle-bot', [SettingsController::class , 'toggleBot']);

            // Users
            Route::get('/users', [UserController::class , 'index']);
            Route::post('/users', [UserController::class , 'store']);
            Route::put('/users/{user}', [UserController::class , 'update']);
            Route::delete('/users/{user}', [UserController::class , 'destroy']);
        }
        );
    });

// React SPA catch-all (ignoring /api)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api/).*');
