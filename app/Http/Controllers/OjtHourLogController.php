<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Models\OjtHourLog;
use App\Models\Student;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OjtHourLogController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting;

    public function store(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('hours.review', $tenant);

        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('tenant.tenant_users', 'id')->where('role', 'student')],
            'log_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'activity' => ['required', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
        ]);

        $hourLog = OjtHourLog::query()->create($data + [
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ]);

        $this->applyApprovedHourDifference($hourLog->student_id, 0.0, $hourLog->status === 'approved' ? (float) $hourLog->hours : 0.0);

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'hours'],
            'OJT hour log recorded.'
        );
    }

    public function update(Request $request, CurrentTenant $currentTenant, OjtHourLog $hour): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('hours.review', $tenant);

        $previousApprovedHours = $hour->status === 'approved' ? (float) $hour->hours : 0.0;

        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('tenant.tenant_users', 'id')->where('role', 'student')],
            'log_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'activity' => ['required', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
        ]);

        $hour->update($data + [
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ]);

        $currentApprovedHours = $hour->status === 'approved' ? (float) $hour->hours : 0.0;
        $this->applyApprovedHourDifference($hour->student_id, $previousApprovedHours, $currentApprovedHours);

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'hours'],
            'OJT hour log updated.'
        );
    }

    public function reviewSupervisor(Request $request, CurrentTenant $currentTenant, OjtHourLog $hour): RedirectResponse
    {
        $tenant = $currentTenant->tenant();
        $supervisor = Auth::guard('supervisor')->user();

        abort_unless($tenant && $supervisor, 404);
        $this->authorizeTenantPermission('hours.review', $tenant);

        $student = Student::query()->findOrFail($hour->student_id);

        abort_unless(
            $supervisor->partner_company_id
            && $student->partner_company_id === $supervisor->partner_company_id,
            403
        );

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
        ]);

        $previousApprovedHours = $hour->status === 'approved' ? (float) $hour->hours : 0.0;

        $hour->update([
            'status' => $data['status'],
            'supervisor_name' => $hour->supervisor_name ?: $supervisor->name,
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ]);

        $currentApprovedHours = $hour->status === 'approved' ? (float) $hour->hours : 0.0;
        $this->applyApprovedHourDifference($hour->student_id, $previousApprovedHours, $currentApprovedHours);

        return redirect()->to($this->tenantRoute($tenant, 'supervisor.dashboard').'#logs')
            ->with('status', 'Student hour log reviewed.');
    }

    public function storeStudent(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();
        /** @var Student|null $student */
        $student = Auth::guard('student')->user();

        abort_unless($tenant && $student, 404);
        $this->authorizeTenantPermission('hours.submit', $tenant);

        $data = $request->validate([
            'log_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'activity' => ['required', 'string', 'max:1000'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
        ]);

        OjtHourLog::query()->create([
            'student_id' => $student->getKey(),
            'log_date' => $data['log_date'],
            'hours' => $data['hours'],
            'activity' => $data['activity'],
            'status' => 'pending',
            'supervisor_name' => $data['supervisor_name'] ?? null,
            'approved_at' => null,
        ]);

        return redirect()->to($this->tenantRoute($tenant, 'student.dashboard').'?section=logs')
            ->with('status', 'Hour log submitted. Your supervisor or coordinator can now review it.');
    }

    protected function applyApprovedHourDifference(int $studentId, float $previousApprovedHours, float $currentApprovedHours): void
    {
        $difference = $currentApprovedHours - $previousApprovedHours;

        if ($difference === 0.0) {
            return;
        }

        Student::query()
            ->whereKey($studentId)
            ->update([
                'completed_hours' => DB::raw('GREATEST(completed_hours + '.((float) $difference).', 0)'),
            ]);
    }
}
