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
use App\Http\Controllers\Panel\CoverageController;
use App\Http\Controllers\Panel\CustomerController;
use App\Http\Controllers\Panel\PlanController;
use App\Http\Controllers\Panel\SalesLeadController;

// Panel APIs using 'web' middleware for sessions natively
Route::group(['prefix' => 'api', 'middleware' => ['web']], function () {
    // Auth
    Route::post('/auth/login', [AuthController::class , 'login']);

    // Protected panel API routes using standard web login state
    Route::middleware(['auth'])->group(function () {
            Route::post('/auth/logout', [AuthController::class , 'logout']);
            Route::get('/auth/user', [AuthController::class , 'user']);

            // Tickets
            Route::get('/tickets', [TicketController::class , 'index']);
            Route::get('/tickets/{ticket}', [TicketController::class , 'show']);
            Route::put('/tickets/{ticket}', [TicketController::class , 'update']);

            // Users Management
            Route::get('/users', [UserController::class , 'index']);
            Route::post('/users', [UserController::class , 'store']);
            Route::delete('/users/{user}', [UserController::class , 'destroy']);

            // Coverage Areas
            Route::post('/coverage/import', [CoverageController::class , 'import']);
            Route::delete('/coverage/clear-all', [CoverageController::class , 'clearAll']);
            Route::apiResource('/coverage', CoverageController::class);

            // Customers Management
            Route::apiResource('/customers', CustomerController::class);
            Route::post('/customers/{customer}/services', [CustomerController::class , 'addService']);
            Route::put('/customer-services/{service}', [CustomerController::class , 'updateService']);
            Route::delete('/customer-services/{service}', [CustomerController::class , 'removeService']);

            // Plans Management
            Route::post('/plans/import', [PlanController::class , 'importPlans']);
            Route::post('/plans/{plan}/toggle', [PlanController::class , 'toggleActive']);
            Route::apiResource('/plans', PlanController::class);

            // Leads / Sales
            Route::get('/leads/stats', [SalesLeadController::class , 'stats']);
            Route::get('/leads', [SalesLeadController::class , 'index']);
            Route::put('/leads/{salesLead}', [SalesLeadController::class , 'update']);
            Route::delete('/leads/{salesLead}', [SalesLeadController::class , 'destroy']);

            // Dashboard
            Route::get('/dashboard/stats', [DashboardController::class , 'index']);

            // Conversations
            Route::get('/conversations', [ConversationController::class , 'index']);
            Route::get('/conversations/{conversation}', [ConversationController::class , 'show']);
            Route::post('/conversations/{conversation}/assign', [ConversationController::class , 'assignAgent']);
            Route::post('/conversations/{conversation}/take-over', [ConversationController::class , 'takeOver']);
            Route::post('/conversations/{conversation}/reactivate-bot', [ConversationController::class , 'reactivateBot']);
            Route::post('/conversations/{conversation}/close', [ConversationController::class , 'close']);
            Route::delete('/conversations/{conversation}', [ConversationController::class , 'destroy']);

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

            // Flow Steps
            Route::post('/flows/{flow}/steps', [FlowController::class , 'storeStep']);
            Route::put('/flow-steps/{step}', [FlowController::class , 'updateStep']);
            Route::delete('/flow-steps/{step}', [FlowController::class , 'destroyStep']);

            // Contacts
            Route::get('/contacts', [ContactController::class , 'index']);
            Route::get('/contacts/{contact}', [ContactController::class , 'show']);
            Route::put('/contacts/{contact}', [ContactController::class , 'update']);

            // Settings
            Route::get('/settings', [SettingsController::class , 'index']);
            Route::put('/settings', [SettingsController::class , 'update']);
            Route::post('/settings/toggle-bot', [SettingsController::class , 'toggleBot']);

        }
        );
    });

// React SPA catch-all (ignoring /api)
Route::get('/update-flows', function () {
    $flowId = App\Models\BotFlowStep::where('step_key', 'ask_cp')->value('bot_flow_id');
    if (!$flowId)
        return 'Flow not found';

    App\Models\BotFlowStep::updateOrCreate(
    ['step_key' => 'confirm_neighborhood'],
    [
        'bot_flow_id' => $flowId,
        'message_text' => "¡Tenemos cobertura en tu código postal! 🚀\n\n📍 CP: {{zip_code}}\n🏘️ Zonas cubiertas: {{coverage_zones}}\n\n⚠️ ¿Tu colonia o calle se encuentra en esta lista?",
        'response_type' => 'buttons',
        'options' => [
            ['id' => 'yes_colonia', 'title' => '✅ Sí, mi colonia está', 'next_step' => 'coverage_success'],
            ['id' => 'no_colonia', 'title' => '❌ No está', 'next_step' => 'select_service_type']
        ],
        'is_active' => true,
    ]
    );

    App\Models\BotFlowStep::updateOrCreate(
    ['step_key' => 'coverage_success'],
    [
        'bot_flow_id' => $flowId,
        'message_text' => "¡Excelente! 🎉 Tienes cobertura confirmada.\n\n¿Qué te gustaría hacer ahora?",
        'response_type' => 'buttons',
        'options' => [
            ['id' => 'opt_plans', 'title' => 'Ver Planes', 'next_flow' => 'planes'],
            ['id' => 'opt_agent', 'title' => 'Hablar con Asesor', 'action' => 'escalate_agent']
        ],
        'is_active' => true,
    ]
    );

    App\Models\BotFlowStep::updateOrCreate(
    ['step_key' => 'select_service_type'],
    [
        'bot_flow_id' => $flowId,
        'message_text' => "Entendido. Para poder brindarte la alternativa correcta, ¿El servicio de internet que buscas es para tu *Hogar* o para una *Empresa/Negocio*?",
        'response_type' => 'buttons',
        'options' => [
            ['id' => 'para_hogar', 'title' => '🏠 Para Hogar', 'next_step' => 'no_coverage_home'],
            ['id' => 'para_empresa', 'title' => '🏢 Para Empresa', 'next_step' => 'escalate_empresa']
        ],
        'is_active' => true,
    ]
    );

    App\Models\BotFlowStep::updateOrCreate(
    ['step_key' => 'no_coverage_home'],
    [
        'bot_flow_id' => $flowId,
        'message_text' => "😔 Lamentamos mucho no tener cobertura en tu zona para servicio residencial en este momento.\n\nTe recomendamos estar pendiente de nuestras redes sociales para conocer nuevas zonas de cobertura en el futuro.\n\n¡Gracias por tu interés en *BGITAL*! 👋",
        'response_type' => 'action_only',
        'action_type' => 'close_conversation',
        'is_active' => true,
    ]
    );

    App\Models\BotFlowStep::updateOrCreate(
    ['step_key' => 'escalate_empresa'],
    [
        'bot_flow_id' => $flowId,
        'message_text' => "¡Excelente! Para servicios empresariales o dedicados, podemos evaluar la viabilidad técnica para extender nuestra infraestructura a tu zona.\n\nUn ejecutivo especializado se pondrá en contacto contigo a la brevedad.",
        'response_type' => 'action_only',
        'action_type' => 'escalate_agent',
        'action_config' => ['reason' => 'Solicitud de factibilidad para EMPRESA fuera de zona inicial'],
        'is_active' => true,
    ]
    );

    App\Models\BotFlowStep::where('step_key', 'check_coverage')->delete();

    return 'Steps updated successfully!';
});

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api/).*');
