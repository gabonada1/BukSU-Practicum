<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Models\Student;
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
        $students = $company
            ? Student::query()
                ->with(['requirements', 'hourLogs', 'applications.partnerCompany'])
                ->where(function ($query) use ($company) {
                    $query->where('partner_company_id', $company->getKey())
                        ->orWhereHas('applications', function ($applicationQuery) use ($company) {
                            $applicationQuery
                                ->where('partner_company_id', $company->getKey())
                                ->whereIn('status', ['pending', 'accepted', 'deployed']);
                        });
                })
                ->latest()
                ->get()
            : collect();
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
