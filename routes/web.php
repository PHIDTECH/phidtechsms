<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\PhoneAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSenderIDController;
use App\Http\Controllers\AdminCampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SenderIDController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\SmsApiController;
use App\Http\Controllers\AdminSmsController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;

// Landing page: show marketing homepage to guests, dashboard for authenticated users
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('landing.home');
})->name('home');

// Test mail endpoint (temporary)
Route::get('/test-mail', function () {
    $to = request('to', 'rodgencetv@gmail.com');
    $subject = 'Phidtech SMS Test Email';
    $body = "Hello,\n\nThis is a test email from Phidtech SMS.\nIf you received this, SMTP is working.\n\nRegards,\nPhidtech SMS";
    try {
        Mail::raw($body, function ($m) use ($to, $subject) {
            $m->to($to)->subject($subject);
        });
        return response()->json(['success' => true, 'to' => $to]);
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
})->name('test-mail');

// Default login/register routes redirect to email versions
Route::get('/login', function () {
    return redirect()->route('email.login');
})->name('login');

Route::get('/register', function () {
    return redirect()->route('email.register');
})->name('register');

// Email-based authentication routes (alternative access)
Route::prefix('auth/email')->name('email.')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
    
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.submit');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register.submit');
});

// Authentication Routes are handled by Auth::routes() at the bottom of this file

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/modern', [DashboardController::class, 'modern'])->name('dashboard.modern');

    
    // Wallet routes
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('/topup', [WalletController::class, 'showTopUp'])->name('topup');
        Route::post('/topup', [WalletController::class, 'topUp'])->name('topup.process');
        Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
        Route::post('/calculate-cost', [WalletController::class, 'calculateCost'])->name('calculate-cost');
    });
    
    // API routes for AJAX calls
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/user/balance', [DashboardController::class, 'getSmsCredits'])->name('user.balance');
    });

    // User API key management (simple JSON endpoint)
    Route::get('/user/api-keys', [ApiKeyController::class, 'index'])->name('user.api-keys.index');
    Route::post('/user/api-keys', [ApiKeyController::class, 'create'])->name('user.api-keys.create');
    Route::post('/user/api-keys/{apiKey}', [ApiKeyController::class, 'update'])->name('user.api-keys.update');
    Route::post('/user/api-keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])->name('user.api-keys.revoke');
    Route::post('/user/api-keys/{apiKey}/restore', [ApiKeyController::class, 'restore'])->name('user.api-keys.restore');
    Route::post('/user/api-keys/{apiKey}/delete', [ApiKeyController::class, 'destroy'])->name('user.api-keys.destroy');

    // Backward-compatible route used by dashboard JS
    Route::get('/sms/credits', [DashboardController::class, 'getSmsCredits'])->name('sms.credits');

    // Quick SMS from dashboard
    Route::post('/sms/send-quick', [DashboardController::class, 'sendQuickSms'])->name('sms.send-quick');
    
    // Profile routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/security', [ProfileController::class, 'security'])->name('security');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    });
    
    // Payment routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/create', [PaymentController::class, 'create'])->name('create');
        Route::get('/status/{payment}', [PaymentController::class, 'status'])->name('status');
        Route::get('/success', [PaymentController::class, 'success'])->name('success');
        Route::get('/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    });

    // User Sender IDs
    Route::resource('sender-ids', SenderIDController::class);

    // SMS Templates
    Route::resource('sms-templates', SmsTemplateController::class);
    // Custom actions for SMS templates
    Route::match(['get','post'], '/sms-templates/{smsTemplate}/duplicate', [SmsTemplateController::class, 'duplicate'])
        ->whereNumber('smsTemplate')
        ->name('sms-templates.duplicate');
    Route::patch('/sms-templates/{smsTemplate}/toggle-status', [SmsTemplateController::class, 'toggleStatus'])
        ->whereNumber('smsTemplate')
        ->name('sms-templates.toggle-status');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/delivery', [ReportController::class, 'deliveryReport'])->name('delivery');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });

    // Contacts Management
    Route::prefix('contacts')->name('contacts.')->group(function () {
        // Contact CRUD - View permissions
        Route::middleware(['contact.permission:view-contacts'])->group(function () {
            Route::get('/', [ContactController::class, 'index'])->name('index');
            Route::get('/address-books', [ContactController::class, 'listAddressBooks'])->name('address-books.index');
            Route::get('/address-books/{addressBookId}/contacts', [ContactController::class, 'fetchAddressBookContacts'])->name('address-books.contacts');
            Route::get('/{contact}', [ContactController::class, 'show'])->whereNumber('contact')->name('show');
        });
        
        // Contact CRUD - Manage permissions
        Route::middleware(['contact.permission:manage-contacts'])->group(function () {
            Route::post('/', [ContactController::class, 'store'])->name('store');
            Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
            Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
        });
        
        // Contact Export - Export permissions
        Route::middleware(['contact.permission:export-contacts'])->group(function () {
            Route::get('/export/csv', [ContactController::class, 'export'])->name('export');
            Route::get('/address-books/{addressBookId}/export', [ContactController::class, 'exportAddressBookContacts'])->name('address-books.export');
        });
        
        // Contact Groups - Group management permissions
        Route::middleware(['contact.permission:manage-contact-groups'])->prefix('groups')->name('groups.')->group(function () {
            Route::post('/', [ContactController::class, 'storeGroup'])->name('store');
            Route::put('/{group}', [ContactController::class, 'updateGroup'])->name('update');
            Route::delete('/{group}', [ContactController::class, 'destroyGroup'])->name('destroy');
        });
        
        // Contact Import - Available to all authenticated users
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [ContactImportController::class, 'index'])->name('index');
            Route::get('/sheet', [ContactImportController::class, 'showSheet'])->name('sheet');
            Route::post('/upload', [ContactImportController::class, 'uploadFile'])->name('upload');
            Route::post('/mapping', [ContactImportController::class, 'processMapping'])->name('mapping');
            Route::get('/groups', [ContactImportController::class, 'getContactGroups'])->name('groups');
            Route::get('/{import}/rows', [ContactImportController::class, 'getValidatedRows'])->name('rows');
            Route::post('/process', [ContactImportController::class, 'processImport'])->name('process');
            Route::get('/{import}/status', [ContactImportController::class, 'getImportStatus'])->name('status');
            Route::get('/{import}/errors', [ContactImportController::class, 'downloadErrorReport'])->name('errors');
        });
        
        // Contact Group Creation in Import - Group management permissions
        Route::middleware(['contact.permission:manage-contact-groups'])->prefix('import')->name('import.')->group(function () {
            Route::post('/groups', [ContactImportController::class, 'createContactGroup'])->name('groups.create');
        });
    });
});

// Webhook routes (no auth required)
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');



// Campaign Routes
Route::middleware(['auth'])->prefix('campaigns')->name('campaigns.')->group(function () {
    // Campaign Dashboard
    Route::get('/', [CampaignController::class, 'index'])->name('index');

    // CRUD
    Route::get('/create', [CampaignController::class, 'create'])->name('create');
    Route::post('/', [CampaignController::class, 'store'])->name('store');
    Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
    Route::get('/{campaign}/edit', [CampaignController::class, 'edit'])->name('edit');
    Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update');
    Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');

    // AJAX routes for campaigns (avoid double prefix)
    Route::get('/template/{id}', [CampaignController::class, 'getTemplate'])->name('template');
    Route::post('/calculate-cost', [CampaignController::class, 'calculateCost'])->name('calculate-cost');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats'])->name('dashboard.stats');
    
    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminController::class, 'users'])->name('index');
        Route::get('/{user}', [AdminController::class, 'showUser'])->name('show');
        Route::get('/{user}/edit', [AdminController::class, 'editUser'])->name('edit');
        Route::put('/{user}', [AdminController::class, 'updateUser'])->name('update');
        Route::post('/{user}/deduct-credits', [AdminController::class, 'deductCredits'])->name('deduct-credits');
        Route::post('/{user}/add-credits', [AdminController::class, 'addCredits'])->name('add-credits');
        Route::post('/{user}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('toggle-status');
    });
    
    // Payment Management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [AdminController::class, 'payments'])->name('index');
        Route::post('/{payment}/approve', [AdminController::class, 'approvePayment'])->name('approve');
        Route::post('/{payment}/reject', [AdminController::class, 'rejectPayment'])->name('reject');
    });
    
    // Sender ID Management
    Route::prefix('sender-ids')->name('sender-ids.')->group(function () {
        Route::get('/', [AdminSenderIDController::class, 'index'])->name('index');
        Route::get('/{senderID}', [AdminSenderIDController::class, 'show'])->name('show');
        Route::post('/{senderID}/approve', [AdminSenderIDController::class, 'approve'])->name('approve');
        Route::post('/{senderID}/reject', [AdminSenderIDController::class, 'reject'])->name('reject');
        Route::get('/{senderID}/download/{type}', [AdminSenderIDController::class, 'downloadDocument'])->name('download-document');
        Route::get('/{senderID}/download-additional/{index}', [AdminSenderIDController::class, 'downloadAdditionalDocument'])->name('download-additional');
        Route::post('/assign', [AdminSenderIDController::class, 'assign'])->name('assign');
        Route::post('/bulk-action', [AdminSenderIDController::class, 'bulkAction'])->name('bulk-action');
    });
    
    // Campaign Management
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [AdminCampaignController::class, 'index'])->name('index');
        Route::get('/{campaign}', [AdminCampaignController::class, 'show'])->name('show');
        Route::get('/{campaign}/edit', [AdminCampaignController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [AdminCampaignController::class, 'update'])->name('update');
        Route::post('/{campaign}/cancel', [AdminCampaignController::class, 'cancel'])->name('cancel');
        Route::delete('/{campaign}', [AdminCampaignController::class, 'destroy'])->name('destroy');
    });
    
    // Admin Send Message & SMS Management
    Route::prefix('sms')->name('sms.')->group(function () {
        // Send Message routes
        Route::get('/', [AdminSmsController::class, 'index'])->name('index');
        Route::get('/compose', [AdminSmsController::class, 'compose'])->name('compose');
        Route::post('/send', [AdminSmsController::class, 'send'])->name('send');
        Route::get('/history', [AdminSmsController::class, 'history'])->name('history');
        Route::get('/users', [AdminSmsController::class, 'getUsers'])->name('users');
        
        // SMS Balance Management
        Route::post('/sync-balance', [AdminController::class, 'syncSmsBalance'])->name('sync-balance');
        Route::post('/update-balance', [AdminController::class, 'updateSmsBalance'])->name('update-balance');
        Route::get('/transactions', [AdminController::class, 'smsTransactions'])->name('transactions');
        Route::post('/credit-user', [AdminController::class, 'creditSmsToUser'])->name('credit-user');
        Route::get('/balance-status', [AdminController::class, 'getSmsBalanceStatus'])->name('balance-status');
        Route::get('/beem-live-balance', [AdminController::class, 'getBeemLiveBalance'])->name('beem-live-balance');

        // Beem Dashboard Balance Synchronization
        Route::post('/sync-beem-balance', [AdminController::class, 'syncBeemBalance'])->name('sync-beem-balance');
        Route::post('/configure-beem-dashboard', [AdminController::class, 'configureBeemDashboard'])->name('configure-beem-dashboard');
        Route::get('/beem-dashboard-status', [AdminController::class, 'getBeemDashboardStatus'])->name('beem-dashboard-status');
    });
    
    // API Settings Management
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AdminController::class, 'settings'])->name('index');
        Route::get('/api', [AdminController::class, 'apiSettings'])->name('api');
        Route::post('/beem', [AdminController::class, 'updateBeemSettings'])->name('beem.update');
        Route::post('/selcom', [AdminController::class, 'updateSelcomSettings'])->name('selcom.update');
    });
    
    // Sender ID Sync
    Route::post('/sync-sender-ids', [AdminController::class, 'syncSenderIds'])->name('sync-sender-ids');
    Route::post('/clear-and-sync-sender-ids', [AdminController::class, 'clearAndSyncSenderIds'])->name('clear-and-sync-sender-ids');
    Route::post('/request-sender-id', [AdminController::class, 'requestSenderId'])->name('request-sender-id');
    Route::get('/seed-phidtech-sender', [AdminController::class, 'seedPhidtechSender'])->name('seed-phidtech-sender');
    Route::get('/cleanup-sender-ids', [AdminController::class, 'cleanupSenderIds'])->name('cleanup-sender-ids');
    Route::get('/delete-sender-ids/{name?}', [AdminController::class, 'deleteSenderIdsByName'])->name('delete-sender-ids');
    Route::get('/delete-all-except-nyabiyonza', [AdminController::class, 'deleteAllExcept'])->name('delete-all-except');
    Route::get('/delete-all-payments-except-nyabiyonza', [AdminController::class, 'deleteAllPaymentsExcept'])->name('delete-all-payments-except');
    Route::get('/update-beem-credentials', [AdminController::class, 'updateBeemCredentials'])->name('update-beem-credentials');
    
    // Admin Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminReportController::class, 'index'])->name('index');
        Route::get('/export', [App\Http\Controllers\AdminReportController::class, 'export'])->name('export');
    });
    
    // Balance Synchronization (Legacy)
    Route::prefix('balance')->name('balance.')->group(function () {
        Route::post('/sync', [AdminController::class, 'syncBeemBalance'])->name('sync');
        Route::get('/beem', [AdminController::class, 'getBeemBalance'])->name('beem');
    });
});

// Webhook routes (public, no auth required)
Route::prefix('webhooks')->group(function () {
    Route::post('/beem/dlr', [WebhookController::class, 'beemDeliveryReport'])->name('webhooks.beem.dlr');
    Route::post('/selcom/payment', [WebhookController::class, 'selcomPayment'])->name('webhooks.selcom.payment');
    Route::any('/test', [WebhookController::class, 'test'])->name('webhooks.test');
    Route::get('/health', [WebhookController::class, 'health'])->name('webhooks.health');
});

// Standard Laravel Authentication Routes (enabled - using email/password)
Auth::routes(['verify' => false]);

// Phone authentication routes disabled; use email auth exclusively

// Public API v1 (authenticated via API key)
Route::prefix('api/v1')->middleware(['apikey','throttle:60,1'])->group(function () {
    Route::post('/sms/send', [SmsApiController::class, 'send']);
});

// Public API documentation page
Route::get('/docs/api', function () {
    return view('docs.api');
});
