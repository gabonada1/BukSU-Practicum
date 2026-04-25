<?php

use App\Http\Controllers\Central\CentralAuthController;
use App\Http\Controllers\Central\CentralDashboardController;
use App\Http\Controllers\Central\PlanApplicationController;
use App\Http\Controllers\Central\SystemUpdateController;
use App\Http\Controllers\Central\SupportTicketController;
use App\Http\Controllers\Central\TenantProvisionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('central.domain')->group(function () {
    Route::post('/apply', [PlanApplicationController::class, 'store'])->name('central.plan-applications.store');
    Route::get('/apply/{application}/success', [PlanApplicationController::class, 'success'])->name('central.plan-applications.success');
    Route::get('/apply/{application}/cancel', [PlanApplicationController::class, 'cancel'])->name('central.plan-applications.cancel');

    Route::get('/central', function () {
        return Auth::guard('central_superadmin')->check()
            ? redirect()->route('central.dashboard')
            : redirect()->route('central.login');
    })->name('central.home');

    Route::middleware('guest:central_superadmin')->group(function () {
        Route::get('/central/login', [CentralAuthController::class, 'create'])->name('central.login');
        Route::post('/central/login', [CentralAuthController::class, 'store'])->name('central.login.store');
    });

    Route::post('/central/logout', [CentralAuthController::class, 'destroy'])
        ->middleware('auth:central_superadmin')
        ->name('central.logout');

    Route::middleware('auth:central_superadmin')->group(function () {
        Route::get('/central/dashboard', CentralDashboardController::class)->name('central.dashboard');
        Route::get('/central/system-updates', [SystemUpdateController::class, 'index'])->name('central.updates.index');
        Route::post('/central/system-updates/sync-tags', [SystemUpdateController::class, 'syncTags'])->name('central.updates.sync-tags');
        Route::post('/central/system-updates', [SystemUpdateController::class, 'store'])->name('central.updates.store');
        Route::get('/central/support', [SupportTicketController::class, 'index'])->name('central.support.index');
        Route::patch('/central/support/{ticket}', [SupportTicketController::class, 'update'])->name('central.support.update');
        Route::post('/central/applications/{application}/approve', [PlanApplicationController::class, 'approve'])->name('central.plan-applications.approve');
        Route::post('/central/applications/{application}/reject', [PlanApplicationController::class, 'reject'])->name('central.plan-applications.reject');
        Route::patch('/central/tenants/{tenant}', [TenantProvisionController::class, 'update'])->name('central.tenants.update');
        Route::patch('/central/tenants/{tenant}/status', [TenantProvisionController::class, 'updateStatus'])->name('central.tenants.status');
        Route::post('/central/tenants/{tenant}/notify', [TenantProvisionController::class, 'notify'])->name('central.tenants.notify');
        Route::delete('/central/tenants/{tenant}', [TenantProvisionController::class, 'destroy'])->name('central.tenants.destroy');
    });
});
