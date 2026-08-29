<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\TransactionController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ?芸?璅⊥?餃 (?皜祈岫靘踹嚗?芰?仿?閮剛??亦洵銝?蝙?刻?
use App\Http\Controllers\AuthController;
Route::middleware(['web'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::middleware(['auth'])->group(function () {
        Route::get('/join-family', [AuthController::class, 'showJoinFamily'])->name('join-family');
        Route::post('/join-family', [AuthController::class, 'joinFamily']);
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        if (! Auth::check()) {
            $user = User::where('account', 'parent')->first() ?? User::first();
            if ($user) Auth::login($user);
        }
        return redirect()->route('dashboard');
    });

    
    // === ?咱撠惇蝪∪?閬? (P3) ===
    Route::get('/child-dashboard', [\App\Http\Controllers\ChildDashboardController::class, 'index'])->name('child.dashboard');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('edit');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::patch('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\SubscriptionController::class, 'store'])->name('store');
        Route::post('/{subscription}/toggle', [\App\Http\Controllers\SubscriptionController::class, 'togglePause'])->name('toggle');
        Route::post('/{subscription}/convert', [\App\Http\Controllers\SubscriptionController::class, 'convertExpense'])->name('convert');
        Route::delete('/{subscription}', [\App\Http\Controllers\SubscriptionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CategoryController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\CategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('destroy');
    });
    
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MemberController::class, 'index'])->name('index');
        Route::post('/child-direct', [\App\Http\Controllers\MemberController::class, 'storeChildDirect'])->name('child-direct');
        Route::post('/invite-child', [\App\Http\Controllers\MemberController::class, 'inviteChild'])->name('invite-child');
        Route::post('/{member}/toggle', [\App\Http\Controllers\MemberController::class, 'toggleStatus'])->name('toggle');
        Route::post('/{member}/remove', [\App\Http\Controllers\MemberController::class, 'destroy'])->name('remove');
    });

    Route::prefix('child-limits')->name('child-limits.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ChildLimitController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\ChildLimitController::class, 'store'])->name('store');
        Route::put('/{child_limit}', [\App\Http\Controllers\ChildLimitController::class, 'update'])->name('update');
        Route::delete('/{child_limit}', [\App\Http\Controllers\ChildLimitController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('child-wallet')->name('child-wallet.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ChildWalletController::class, 'index'])->name('index');
        Route::post('/give-allowance', [\App\Http\Controllers\ChildWalletController::class, 'giveAllowance'])->name('give-allowance');
        Route::post('/deposit', [\App\Http\Controllers\ChildWalletController::class, 'deposit'])->name('deposit');
    });

    Route::prefix('saving-goals')->name('saving-goals.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SavingGoalController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\SavingGoalController::class, 'store'])->name('store');
        Route::put('/{saving_goal}', [\App\Http\Controllers\SavingGoalController::class, 'update'])->name('update');
        Route::delete('/{saving_goal}', [\App\Http\Controllers\SavingGoalController::class, 'destroy'])->name('destroy');
        Route::post('/{saving_goal}/deposit', [\App\Http\Controllers\SavingGoalController::class, 'deposit'])->name('deposit');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/monthly', [\App\Http\Controllers\ReportController::class, 'monthly'])->name('monthly');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('read_all');
        Route::post('/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
        Route::match(['DELETE', 'POST'], '/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('audit-logs')->name('audit_logs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('index');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\SettingsController::class, 'profile'])->name('profile');
        Route::post('/profile', [\App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::get('/family', [\App\Http\Controllers\SettingsController::class, 'family'])->name('family');
        Route::put('/family', [\App\Http\Controllers\SettingsController::class, 'updateFamily'])->name('family.update');
        Route::get('/notifications', [\App\Http\Controllers\SettingsController::class, 'notifications'])->name('notifications');
        Route::put('/notifications', [\App\Http\Controllers\SettingsController::class, 'updateNotifications'])->name('notifications.update');
    });

    Route::prefix('data-exchange')->name('data_exchange.')->group(function () {
        Route::get('/', [\App\Http\Controllers\DataExchangeController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\DataExchangeController::class, 'exportCsv'])->name('export');
        Route::post('/import', [\App\Http\Controllers\DataExchangeController::class, 'importCsv'])->name('import');
    });

    Route::prefix('attachments')->name('attachments.')->group(function () {
        Route::post('/{transaction}', [\App\Http\Controllers\AttachmentController::class, 'store'])->name('store');
        Route::delete('/{attachment}', [\App\Http\Controllers\AttachmentController::class, 'destroy'])->name('destroy');
    });

    // Gmail OAuth2 整合 — 將使用者導向 /oauth/gmail 同意畫面;callback 與 status endpoint
    Route::get('/oauth/gmail', [\App\Http\Controllers\GmailOAuthController::class, 'redirect'])->name('oauth.gmail');
    Route::get('/oauth/gmail/callback', [\App\Http\Controllers\GmailOAuthController::class, 'callback'])->name('oauth.gmail.callback');
    Route::get('/oauth/gmail/status', [\App\Http\Controllers\GmailOAuthController::class, 'status'])->name('oauth.gmail.status');

    // PWA Controller Routes
    Route::get('/manifest.json', [\App\Http\Controllers\PwaController::class, 'manifest'])->name('pwa.manifest');
    Route::get('/sw.js', [\App\Http\Controllers\PwaController::class, 'serviceWorker'])->name('pwa.sw');
    Route::get('/offline', [\App\Http\Controllers\PwaController::class, 'offline'])->name('pwa.offline');


    Route::prefix('custom-values')->name('custom_values.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CustomValueController::class, 'index'])->name('index');
        Route::post('/{id}/promote', [\App\Http\Controllers\CustomValueController::class, 'promote'])->name('promote');
        Route::post('/{id}/reject', [\App\Http\Controllers\CustomValueController::class, 'reject'])->name('reject');
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TaskController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\TaskController::class, 'store'])->name('store');
        Route::post('/{task}/report', [\App\Http\Controllers\TaskController::class, 'report'])->name('report');
        Route::post('/{task}/approve', [\App\Http\Controllers\TaskController::class, 'approve'])->name('approve');
        Route::post('/{task}/reject', [\App\Http\Controllers\TaskController::class, 'reject'])->name('reject');
    });

    
    // === PDF ? / ???勗??臬 (P2) ===
    // Driver ??config('pdf.driver') 瘙箏?嚗??閮剔汗?典??唳芋撘?
    // PDF 月報 / 預算報表列印 (P2) — Driver 需 config('pdf.driver'), 否則走網頁列印 fallback
    Route::get('/reports/export/pdf/monthly', [\App\Http\Controllers\PdfExportController::class, 'monthly'])->name('reports.export.pdf.monthly');
    Route::get('/reports/export/pdf/budget', [\App\Http\Controllers\PdfExportController::class, 'budget'])->name('reports.export.pdf.budget');

    // === 摰嗅滬 AI 鞎∪???陛?????===
    Route::get('/family-ai-reports', [\App\Http\Controllers\FamilyAiReportController::class, 'index'])->name('family_ai_reports.index');
    Route::get('/family-ai-reports/{family_ai_report}', [\App\Http\Controllers\FamilyAiReportController::class, 'show'])->name('family_ai_reports.show');
    Route::post('/family-ai-reports/generate', [\App\Http\Controllers\FamilyAiReportController::class, 'generate'])->name('family_ai_reports.generate');

    Route::post('/reports/ai-analysis', [\App\Http\Controllers\ReportController::class, 'aiAnalysis'])->name('reports.ai_analysis');
    Route::get('/export/csv', [\App\Http\Controllers\ExportController::class, 'exportCsv'])->name('export.csv');

    Route::post('/family/switch', [FamilyController::class, 'switch'])->name('family.switch');

    Route::get('/transactions/calendar', [TransactionController::class, 'calendar'])->name('transactions.calendar');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');

    Route::post('/budgets/copy-previous', [\App\Http\Controllers\BudgetController::class, 'copyPrevious'])->name('budgets.copy');
    Route::post('/budgets/copy-last-month', [\App\Http\Controllers\BudgetController::class, 'copyPrevious'])->name('budgets.copy-last-month');
    Route::resource('budgets', \App\Http\Controllers\BudgetController::class);
    Route::post('/recurring-bills/{recurring_bill}/record-now', [\App\Http\Controllers\RecurringBillController::class, 'recordNow'])->name('recurring-bills.record-now');
    Route::resource('recurring-bills', \App\Http\Controllers\RecurringBillController::class);
    
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AccountController::class, 'index'])->name('index');
        Route::get('/{account}', [\App\Http\Controllers\AccountController::class, 'show'])->name('show');
        Route::post('/', [\App\Http\Controllers\AccountController::class, 'store'])->name('store');
        Route::put('/{account}', [\App\Http\Controllers\AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [\App\Http\Controllers\AccountController::class, 'destroy'])->name('destroy');
        Route::post('/transfer', [\App\Http\Controllers\AccountController::class, 'transfer'])->name('transfer');
    });


    // === ?拇郊撽?霅?(2FA) - P2 ===
    // ? / 蝣箄? / ?? / ??Ｙ??Ｗ儔蝣潘???餃嚗?
    Route::middleware(['auth'])->prefix('settings/2fa')->name('settings.2fa.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TwoFactorController::class, 'show'])->name('show');
        Route::post('/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('enable');
        Route::post('/confirm', [\App\Http\Controllers\TwoFactorController::class, 'confirm'])->name('confirm');
        Route::post('/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('disable');
        Route::post('/regenerate-recovery', [\App\Http\Controllers\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('regenerate-recovery');
    });
    // 2FA 撽?蝡舫?嚗?亙?雿??芷? 2FA ??
    Route::middleware(['web'])->group(function () {
        Route::get('/2fa/verify', [\App\Http\Controllers\TwoFactorController::class, 'showVerifyForm'])->name('verify.show');
        Route::post('/2fa/verify', [\App\Http\Controllers\TwoFactorController::class, 'verify']);
    });

    // System Admin Routes
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('families', \App\Http\Controllers\AdminFamilyController::class);
        Route::get('/families/{family}/ai-summary', [\App\Http\Controllers\AdminFamilyController::class, 'aiSummary'])->name('families.ai_summary');
        Route::resource('users', \App\Http\Controllers\AdminUserController::class);
        Route::post('/users/{user}/toggle-admin', [\App\Http\Controllers\AdminUserController::class, 'toggleSystemAdmin'])->name('users.toggle_admin');
        Route::post('/users/{user}/families/attach', [\App\Http\Controllers\AdminUserController::class, 'attachFamily'])->name('users.families.attach');
        Route::put('/users/{user}/families/{family}/role', [\App\Http\Controllers\AdminUserController::class, 'updateFamilyRole'])->name('users.families.update_role');
        Route::delete('/users/{user}/families/{family}', [\App\Http\Controllers\AdminUserController::class, 'detachFamily'])->name('users.families.detach');
        Route::post('/users/{user}/families/detach-all', [\App\Http\Controllers\AdminUserController::class, 'detachAllFamilies'])->name('users.families.detach_all');
        Route::post('/users/{user}/families/{family}/set-primary', [\App\Http\Controllers\AdminUserController::class, 'setPrimaryFamily'])->name('users.families.set_primary');
        Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit_logs');

        // === AI ?箄閮剖? (Token 蝞∠?) ===
        Route::get('/ai-settings', [\App\Http\Controllers\AdminAiSettingController::class, 'index'])->name('ai.index');
        Route::post('/ai-settings', [\App\Http\Controllers\AdminAiSettingController::class, 'update'])->name('ai.update');
        Route::post('/ai-settings/test', [\App\Http\Controllers\AdminAiSettingController::class, 'testConnection'])->name('ai.test');
        Route::post('/ai-settings/broadcast', [\App\Http\Controllers\AdminAiSettingController::class, 'generateBroadcast'])->name('ai.broadcast');

        // === Gmail 連線管理（OAuth UI） ===
        Route::get('/gmail', [\App\Http\Controllers\AdminGmailController::class, 'index'])->name('gmail.index');
        Route::post('/gmail/credentials', [\App\Http\Controllers\AdminGmailController::class, 'saveCredentials'])->name('gmail.save');
        Route::get('/gmail/connect', [\App\Http\Controllers\AdminGmailController::class, 'connect'])->name('gmail.connect');
        Route::post('/gmail/test', [\App\Http\Controllers\AdminGmailController::class, 'testSend'])->name('gmail.test');
        Route::delete('/gmail/disconnect', [\App\Http\Controllers\AdminGmailController::class, 'disconnect'])->name('gmail.disconnect');

        // === ?函???蝮質汗 ===
        Route::get('/categories', [\App\Http\Controllers\AdminCategoryController::class, 'index'])->name('categories.index');
        Route::delete('/categories/{category}', [\App\Http\Controllers\AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        // === 蝟餌絞?撱? ===
        Route::get('/notifications/create', [\App\Http\Controllers\AdminNotificationController::class, 'create'])->name('notifications.create');
        Route::post('/notifications/broadcast', [\App\Http\Controllers\AdminNotificationController::class, 'broadcast'])->name('notifications.broadcast');

        // === 鞈??遢 ===
        Route::get('/backup', [\App\Http\Controllers\AdminBackupController::class, 'index'])->name('backup.index');
        Route::post('/backup', [\App\Http\Controllers\AdminBackupController::class, 'create'])->name('backup.create');
        Route::get('/backup/download/{filename}', [\App\Http\Controllers\AdminBackupController::class, 'download'])->name('backup.download');
        Route::delete('/backup/{filename}', [\App\Http\Controllers\AdminBackupController::class, 'destroy'])->name('backup.destroy');
    });

    // Family Invitation Routes
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\FamilyInvitationController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\FamilyInvitationController::class, 'store'])->name('store');
        Route::post('/{invitation}/resend', [\App\Http\Controllers\FamilyInvitationController::class, 'resend'])->name('resend');
        Route::post('/{invitation}/cancel', [\App\Http\Controllers\FamilyInvitationController::class, 'cancel'])->name('cancel');
    });
    Route::get('/invitation/accept/{token}', [\App\Http\Controllers\FamilyInvitationController::class, 'accept'])->name('invitation.accept');
});




