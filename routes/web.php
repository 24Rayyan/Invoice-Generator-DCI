<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\InvoiceCostController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Mini ERP DCI (Phase 2 with Dynamic Landing Page)
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. PUBLIC / GUEST ROUTES (LOGIN)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Logout Route
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ==========================================
// 2. PROTECTED MODULE ROUTES (AUTHENTICATED & RBAC)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD UTAMA & REDIRECTOR CERDAS ---
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', function () {
        $user = auth()->user();

        // 1. Jika user memiliki akses ke modul 'dashboard', tampilkan dashboard
        if ($user->hasAccess('dashboard')) {
            return app(DocumentController::class)->dashboard();
        }

        // 2. Mapping key modul di DB ke nama route landing page masing-masing
        $moduleRoutes = [
            'invoices' => 'documents.index',
            'customers' => 'customers.index',
            'projects' => 'projects.index',
            'expenses' => 'transactions.index',
            'categories' => 'categories.index',
            'financial_reports' => 'reports.financial_statement',
            'profit_loss' => 'reports.profit_loss',
            'project_margin' => 'reports.project_margin',
            'user_management' => 'management.users.index',
            'settings' => 'settings.index', // <-- Ditambahkan ke smart redirector
            'activity_logs' => 'activity_logs.index',
        ];

        // 3. Iterasi modul milik role user & redirect ke modul pertama yang cocok
        if ($user->role && $user->role->modules) {
            foreach ($user->role->modules as $module) {
                if (isset($moduleRoutes[$module->key]) && $user->hasAccess($module->key)) {
                    return redirect()->route($moduleRoutes[$module->key]);
                }
            }
        }

        // 4. Fallback jika user sama sekali tidak memiliki akses modul aktif
        abort(403, 'Anda tidak memiliki hak akses untuk membuka modul apa pun.');

    })->name('dashboard');

    // --- MANAGEMENT DOKUMEN (INVOICE / PO) ---
    Route::middleware(['module:invoices'])->group(function () {
        Route::get('/purchase-orders', [DocumentController::class, 'purchaseOrders'])->name('purchase-orders.index');
        Route::resource('documents', DocumentController::class);
        Route::patch('/documents/{id}/update-status', [DocumentController::class, 'updateStatus'])->name('documents.update-status');
        Route::post('/documents/export-coretax', [DocumentController::class, 'exportCoretax'])->name('documents.export_coretax');
        Route::post('/documents/export-coretax-xml', [DocumentController::class, 'exportCoretaxXml'])->name('documents.export_coretax_xml');
        Route::post('/documents/export-xml', [DocumentController::class, 'exportXml'])->name('documents.export_xml');
        Route::get('/documents/{id}/pdf', [DocumentController::class, 'downloadPdf'])->name('documents.pdf');
        Route::get('/documents/{id}/delivery-note', [DocumentController::class, 'downloadDeliveryNote'])->name('documents.delivery_note');
        Route::get('/documents/{id}/kwitansi', [DocumentController::class, 'downloadKwitansi'])->name('documents.kwitansi');
        Route::get('/documents/{id}/duplicate', [DocumentController::class, 'duplicate'])->name('documents.duplicate');
        Route::post('/documents/{document}/attach-transactions', [InvoiceCostController::class, 'attach'])->middleware('module:invoice_margin')->name('documents.attach-transactions');
        Route::post('/documents/{document}/transactions/{transaction}/detach', [InvoiceCostController::class, 'detach'])->middleware('module:invoice_margin')->name('documents.detach-transaction');
    });

    Route::middleware(['module:projects'])->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('/projects/{project}/attach-invoices', [ProjectController::class, 'attachInvoices'])->name('projects.attach-invoices');
        Route::post('/projects/{project}/invoices/{document}/detach', [ProjectController::class, 'detachInvoice'])->name('projects.detach-invoice');
        Route::post('/projects/{project}/attach-expenses', [ProjectController::class, 'attachExpenses'])->name('projects.attach-expenses');
        Route::post('/projects/{project}/transactions/{transaction}/detach', [ProjectController::class, 'detachExpense'])->name('projects.detach-expense');
    });

    Route::middleware(['module:customers'])->group(function () {
        Route::resource('customers', CustomerController::class)->except(['show']);
    });

    // --- TRANSAKSI KEUANGAN & PENGELUARAN (EXPENSES / ARUS KAS) ---
    Route::middleware(['module:expenses'])->group(function () {
        Route::resource('transactions', TransactionController::class);
        Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'downloadReceipt'])->name('transactions.receipt');
    });

    Route::middleware(['module:categories'])->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // --- REPORTING ENGINE & FINANCIAL STATEMENTS (REPORTS) ---
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::middleware(['module:financial_reports'])->group(function () {
            Route::get('/financial-statement', [FinancialReportController::class, 'financialStatement'])->name('financial_statement');
            Route::get('/financial-statement/pdf', [FinancialReportController::class, 'exportFinancialStatementPdf'])->name('financial_statement.pdf');
            Route::get('/financial-statement/excel', [FinancialReportController::class, 'exportFinancialStatementExcel'])->name('financial_statement.excel');
        });

        Route::middleware(['module:profit_loss'])->group(function () {
            Route::get('/profit-loss', [FinancialReportController::class, 'profitAndLoss'])->name('profit_loss');
            Route::get('/profit-loss/pdf', [FinancialReportController::class, 'exportProfitAndLossPdf'])->name('profit_loss.pdf');
            Route::get('/profit-loss/excel', [FinancialReportController::class, 'exportProfitAndLossExcel'])->name('profit_loss.excel');
        });

        Route::middleware(['module:project_margin'])->group(function () {
            Route::get('/project-margin', [FinancialReportController::class, 'projectMargin'])->name('project_margin');
            Route::get('/projects', [FinancialReportController::class, 'projects'])->name('projects');
        });
    });

    // --- PENGATURAN PERUSAHAAN (SETTINGS) ---
    Route::middleware(['module:settings'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // --- MODUL MANAGEMENT USER & ROLE (USER ACCESS MODULE) ---
    Route::middleware(['module:user_management'])->prefix('management')->name('management.')->group(function () {
        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        // Roles
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::post('/roles/{role}/grant-modules', [RoleController::class, 'grantModules'])->name('roles.grant-modules');
    });

    // --- LOG AKTIVITAS / AUDIT TRAIL (AKSES DIATUR VIA MODUL RBAC) ---
    Route::middleware(['module:activity_logs'])->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');
        Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity_logs.show');
    });
});
