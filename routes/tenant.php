<?php

use App\Http\Controllers\Tenant\CourseController;
use App\Http\Controllers\Tenant\InternshipApplicationController;
use App\Http\Controllers\Tenant\OjtHourLogController;
use App\Http\Controllers\Tenant\PartnerCompanyController;
use App\Http\Controllers\Tenant\StudentController;
use App\Http\Controllers\Tenant\StudentDashboardController;
use App\Http\Controllers\Tenant\StudentRequirementController;
use App\Http\Controllers\Tenant\SupervisorController;
use App\Http\Controllers\Tenant\SupervisorDashboardController;
use App\Http\Controllers\Tenant\TenantAdminPasswordSetupController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\TenantForgotPasswordController;
use App\Http\Controllers\Tenant\TenantProfileController;
use App\Http\Controllers\Tenant\TenantRbacController;
use App\Http\Controllers\Tenant\TenantRegistrationController;
use App\Http\Controllers\Tenant\TenantReleaseController;
use App\Http\Controllers\Tenant\TenantSupportController;
use App\Http\Controllers\Tenant\TenantUserManagementController;
use Illuminate\Support\Facades\Route;

$loginRoles = ['admin', 'student', 'supervisor'];

$registerTenantRoutes = function (string $namePrefix) use ($loginRoles): void {
    Route::get('/login', [TenantAuthController::class, 'admin'])->name("{$namePrefix}login.default");
    Route::post('/login', [TenantAuthController::class, 'storeAdmin'])->name("{$namePrefix}login.default.store");
    Route::get('/{role}/login', [TenantAuthController::class, 'create'])
        ->whereIn('role', $loginRoles)
        ->name("{$namePrefix}login");
    Route::post('/{role}/login', [TenantAuthController::class, 'store'])
        ->whereIn('role', $loginRoles)
        ->name("{$namePrefix}login.store");
    Route::get('/forgot-password', [TenantForgotPasswordController::class, 'create'])->name("{$namePrefix}password.request");
    Route::post('/forgot-password', [TenantForgotPasswordController::class, 'store'])->name("{$namePrefix}password.email");
    Route::get('/reset-password', [TenantForgotPasswordController::class, 'edit'])->name("{$namePrefix}password.reset");
    Route::post('/reset-password', [TenantForgotPasswordController::class, 'update'])->name("{$namePrefix}password.update");
    Route::get('/{role}/forgot-password', [TenantForgotPasswordController::class, 'create'])
        ->whereIn('role', $loginRoles)
        ->name("{$namePrefix}password.request.role");
    Route::post('/{role}/forgot-password', [TenantForgotPasswordController::class, 'store'])
        ->whereIn('role', $loginRoles)
        ->name("{$namePrefix}password.email.role");
    Route::get('/{role}/reset-password', [TenantForgotPasswordController::class, 'edit'])
        ->whereIn('role', $loginRoles)
        ->name("{$namePrefix}password.reset.role");
    Route::post('/{role}/reset-password', [TenantForgotPasswordController::class, 'update'])
        ->whereIn('role', $loginRoles)
        ->name("{$namePrefix}password.update.role");

    Route::get('/register', [TenantRegistrationController::class, 'create'])->name("{$namePrefix}register.create");
    Route::post('/register', [TenantRegistrationController::class, 'store'])->name("{$namePrefix}register.store");
    Route::get('/register/verify/{token}', [TenantRegistrationController::class, 'verify'])->name("{$namePrefix}register.verify");

    Route::middleware(['auth:tenant_admin', 'tenant.account'])->group(function () use ($namePrefix) {
        Route::post('/admin/logout', [TenantAuthController::class, 'destroy'])
            ->defaults('role', 'admin')
            ->name("{$namePrefix}admin.logout");
        Route::get('/admin/create-password', [TenantAdminPasswordSetupController::class, 'create'])->name("{$namePrefix}admin.password.setup.show");
        Route::post('/admin/create-password', [TenantAdminPasswordSetupController::class, 'store'])->name("{$namePrefix}admin.password.setup.store");
    });

    Route::middleware(['auth:tenant_admin', 'tenant.account', 'tenant.password.updated'])->group(function () use ($namePrefix) {
        Route::get('/admin/profile', [TenantProfileController::class, 'show'])->defaults('role', 'admin')->name("{$namePrefix}admin.profile.show");
        Route::patch('/admin/profile', [TenantProfileController::class, 'update'])->defaults('role', 'admin')->name("{$namePrefix}admin.profile.update");
        Route::put('/admin/profile/password', [TenantProfileController::class, 'updatePassword'])->defaults('role', 'admin')->name("{$namePrefix}admin.profile.password.update");
        Route::post('/admin/profile/branding-settings', [TenantProfileController::class, 'saveBrandingSettings'])->defaults('role', 'admin')->name("{$namePrefix}admin.profile.branding-settings");
        Route::post('/admin/profile/ojt-settings', [TenantProfileController::class, 'saveOjtSettings'])->defaults('role', 'admin')->name("{$namePrefix}admin.profile.ojt-settings");
        Route::get('/admin/updates', [TenantReleaseController::class, 'index'])->name("{$namePrefix}admin.updates.index");
        Route::post('/admin/updates/sync-tags', [TenantReleaseController::class, 'syncTags'])->name("{$namePrefix}admin.updates.sync-tags");
        Route::post('/admin/updates', [TenantReleaseController::class, 'apply'])->name("{$namePrefix}admin.updates.apply");
        Route::get('/admin/support', [TenantSupportController::class, 'index'])->name("{$namePrefix}admin.support.index");
        Route::post('/admin/support', [TenantSupportController::class, 'store'])->name("{$namePrefix}admin.support.store");
        Route::get('/admin/rbac', [TenantRbacController::class, 'index'])->name("{$namePrefix}admin.rbac.index");
        Route::post('/admin/rbac', [TenantRbacController::class, 'update'])->name("{$namePrefix}admin.rbac.update");
        Route::post('/admin/rbac/reset', [TenantRbacController::class, 'reset'])->name("{$namePrefix}admin.rbac.reset");
        Route::post('/courses', [CourseController::class, 'store'])->name("{$namePrefix}courses.store");
        Route::patch('/courses/{course}', [CourseController::class, 'update'])->name("{$namePrefix}courses.update");
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name("{$namePrefix}courses.destroy");
        Route::get('/admin/dashboard', TenantDashboardController::class)->name("{$namePrefix}admin.dashboard");
        Route::post('/admin/companies', [PartnerCompanyController::class, 'store'])->name("{$namePrefix}admin.companies.store");
        Route::patch('/admin/companies/{company}', [PartnerCompanyController::class, 'update'])->name("{$namePrefix}admin.companies.update");
        Route::post('/admin/applications', [InternshipApplicationController::class, 'storeAdmin'])->name("{$namePrefix}admin.applications.store");
        Route::patch('/admin/applications/{application}', [InternshipApplicationController::class, 'updateAdmin'])->name("{$namePrefix}admin.applications.update");
        Route::post('/admin/students', [StudentController::class, 'store'])->name("{$namePrefix}admin.students.store");
        Route::patch('/admin/students/{student}', [StudentController::class, 'update'])->name("{$namePrefix}admin.students.update");
        Route::post('/admin/supervisors', [SupervisorController::class, 'store'])->name("{$namePrefix}admin.supervisors.store");
        Route::patch('/admin/supervisors/{supervisor}', [SupervisorController::class, 'update'])->name("{$namePrefix}admin.supervisors.update");
        Route::post('/admin/requirements', [StudentRequirementController::class, 'store'])->name("{$namePrefix}admin.requirements.store");
        Route::patch('/admin/requirements/{requirement}', [StudentRequirementController::class, 'update'])->name("{$namePrefix}admin.requirements.update");
        Route::get('/admin/hours/export', [OjtHourLogController::class, 'export'])->name("{$namePrefix}admin.hours.export");
        Route::post('/admin/hours', [OjtHourLogController::class, 'store'])->name("{$namePrefix}admin.hours.store");
        Route::patch('/admin/hours/{hour}', [OjtHourLogController::class, 'update'])->name("{$namePrefix}admin.hours.update");
        Route::patch('/admin/users/{type}/{id}', [TenantUserManagementController::class, 'update'])
            ->whereIn('type', ['admin', 'supervisor', 'student'])
            ->name("{$namePrefix}admin.users.update");
    });

    Route::middleware(['auth:supervisor', 'tenant.account'])->group(function () use ($namePrefix) {
        Route::post('/supervisor/logout', [TenantAuthController::class, 'destroy'])
            ->defaults('role', 'supervisor')
            ->name("{$namePrefix}supervisor.logout");
        Route::get('/supervisor/profile', [TenantProfileController::class, 'show'])->defaults('role', 'supervisor')->name("{$namePrefix}supervisor.profile.show");
        Route::patch('/supervisor/profile', [TenantProfileController::class, 'update'])->defaults('role', 'supervisor')->name("{$namePrefix}supervisor.profile.update");
        Route::put('/supervisor/profile/password', [TenantProfileController::class, 'updatePassword'])->defaults('role', 'supervisor')->name("{$namePrefix}supervisor.profile.password.update");
        Route::get('/supervisor/dashboard', SupervisorDashboardController::class)->name("{$namePrefix}supervisor.dashboard");
        Route::patch('/supervisor/hours/{hour}', [OjtHourLogController::class, 'reviewSupervisor'])->name("{$namePrefix}supervisor.hours.update");
    });

    Route::middleware(['auth:student', 'tenant.account'])->group(function () use ($namePrefix) {
        Route::post('/student/logout', [TenantAuthController::class, 'destroy'])
            ->defaults('role', 'student')
            ->name("{$namePrefix}student.logout");
        Route::get('/student/profile', [TenantProfileController::class, 'show'])->defaults('role', 'student')->name("{$namePrefix}student.profile.show");
        Route::patch('/student/profile', [TenantProfileController::class, 'update'])->defaults('role', 'student')->name("{$namePrefix}student.profile.update");
        Route::put('/student/profile/password', [TenantProfileController::class, 'updatePassword'])->defaults('role', 'student')->name("{$namePrefix}student.profile.password.update");
        Route::get('/student/dashboard', StudentDashboardController::class)->name("{$namePrefix}student.dashboard");
        Route::post('/student/applications', [InternshipApplicationController::class, 'storeStudent'])->name("{$namePrefix}student.applications.store");
        Route::post('/student/requirements', [StudentRequirementController::class, 'storeStudent'])->name("{$namePrefix}student.requirements.store");
        Route::post('/student/hours', [OjtHourLogController::class, 'storeStudent'])->name("{$namePrefix}student.hours.store");
    });
};

Route::middleware(['tenant.domain', 'tenant'])->group(function () use ($registerTenantRoutes) {
    $registerTenantRoutes('tenant.');
});
