<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorDashboardController extends Controller
{
    use AuthorizesTenantPermissions;

    public function __invoke(Request $request): View
    {
        $supervisor = Auth::guard('supervisor')->user();

        abort_unless($supervisor, 403);
        $this->authorizeTenantPermission('report.view');

        $company = $supervisor->partnerCompany;
        $students = $company?->students()->with(['requirements', 'hourLogs'])->latest()->get() ?? collect();
        $hourLogs = $students->isEmpty()
            ? collect()
            : $students->pluck('hourLogs')->flatten()->sortByDesc('log_date')->take(10);
        $tenant = app(\App\Support\Tenancy\CurrentTenant::class)->tenant();
        $portalTitle = data_get($tenant?->settings, 'branding.portal_title', config('app.name', 'University Practicum'));
        $section = $request->string('section')->toString();

        if (! in_array($section, ['students', 'logs'], true)) {
            $section = 'students';
        }

        return view('tenant.supervisor.dashboard', [
            'tenant' => $tenant,
            'pageTitle' => 'Company Supervisor Dashboard | '.$portalTitle,
            'supervisor' => $supervisor,
            'company' => $company,
            'students' => $students,
            'hourLogs' => $hourLogs,
            'currentSection' => $section,
        ]);
    }
}
