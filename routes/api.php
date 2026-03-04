<?php

use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Panel\AuthController;
use App\Http\Controllers\Panel\ContactController;
use App\Http\Controllers\Panel\ConversationController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\FlowController;
use App\Http\Controllers\Panel\MessageController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\UserController;
use Illuminate\Support\Facades\Route;

// WhatsApp Webhook — no auth
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class , 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class , 'receive']);
