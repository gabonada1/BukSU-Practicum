<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Controllers\Concerns\RecordsTenantAudit;
use App\Http\Requests\OjtHourLogRequest;
use App\Http\Requests\ReviewOjtHourLogRequest;
use App\Http\Requests\SubmitOjtHourLogRequest;
use App\Models\OjtHourLog;
use App\Models\Student;
use App\Support\Reports\PlainPdf;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OjtHourLogController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting, RecordsTenantAudit;

    public function store(OjtHourLogRequest $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('hours.review', $tenant);

        $data = $request->validated();

        $hourLog = OjtHourLog::query()->create($data + [
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ]);

        $this->applyApprovedHourDifference($hourLog->student_id, 0.0, $hourLog->status === 'approved' ? (float) $hourLog->hours : 0.0);
        $this->auditTenantActivity($request, 'created hour log', $hourLog, null, $hourLog->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'hours'],
            'OJT hour log recorded.'
        );
    }

    public function update(OjtHourLogRequest $request, CurrentTenant $currentTenant, OjtHourLog $hour): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('hours.review', $tenant);

        $previousApprovedHours = $hour->status === 'approved' ? (float) $hour->hours : 0.0;
        $oldValues = $hour->toArray();

        $data = $request->validated();

        $hour->update($data + [
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ]);

        $currentApprovedHours = $hour->status === 'approved' ? (float) $hour->hours : 0.0;
        $this->applyApprovedHourDifference($hour->student_id, $previousApprovedHours, $currentApprovedHours);
        $this->auditTenantActivity($request, 'updated hour log', $hour, $oldValues, $hour->fresh()->toArray());

        return $this->redirectToTenantRoute(
            $request,
            $tenant,
            'admin.dashboard',
            ['section' => 'hours'],
            'OJT hour log updated.'
        );
    }

    public function export(CurrentTenant $currentTenant): Response
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('hours.review', $tenant);

        $logs = OjtHourLog::query()
            ->with(['student.partnerCompany'])
            ->orderByDesc('log_date')
            ->orderByDesc('id')
            ->get();

        $generatedAt = now();
        $rows = $logs->map(function (OjtHourLog $log): array {
            return [
                'student_number' => $log->student?->student_number ?: '-',
                'student_name' => $log->student?->full_name ?: 'Unknown student',
                'program' => $log->student?->program ?: '-',
                'company' => $log->student?->partnerCompany?->name ?: '-',
                'date' => $log->log_date?->format('Y-m-d') ?: '-',
                'hours' => rtrim(rtrim(number_format((float) $log->hours, 2), '0'), '.'),
                'status' => strtoupper($log->status),
                'supervisor' => $log->supervisor_name ?: '-',
                'activity' => $log->activity ?: '-',
                'approved_at' => $log->approved_at?->format('Y-m-d H:i') ?: '-',
                'created_at' => $log->created_at?->format('Y-m-d H:i') ?: '-',
            ];
        });

        $pages = $this->hourLogPdfPages(
            $tenant->name,
            $generatedAt->format('M d, Y h:i A'),
            $rows->all()
        );

        $fileName = 'ojt-hour-logs-'.$generatedAt->format('Y-m-d-His').'.pdf';

        return response(PlainPdf::render($pages, 'OJT Hour Logs'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function reviewSupervisor(ReviewOjtHourLogRequest $request, CurrentTenant $currentTenant, OjtHourLog $hour): RedirectResponse
    {
        $tenant = $currentTenant->tenant();
        $supervisor = Auth::guard('supervisor')->user();

        abort_unless($tenant && $supervisor, 404);
        $this->authorizeTenantPermission('hours.review', $tenant);

        $student = Student::query()->findOrFail($hour->student_id);

        $hasCompanyAccess = $supervisor->partner_company_id
            && (
                $student->partner_company_id === $supervisor->partner_company_id
                || $student->applications()
                    ->where('partner_company_id', $supervisor->partner_company_id)
                    ->whereIn('status', ['pending', 'accepted', 'deployed'])
                    ->exists()
            );

        abort_unless($hasCompanyAccess, 403);

        $data = $request->validated();

        $previousApprovedHours = $hour->status === 'approved' ? (float) $hour->hours : 0.0;
        $oldValues = $hour->toArray();

        $hour->update([
            'status' => $data['status'],
            'supervisor_name' => $hour->supervisor_name ?: $supervisor->name,
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ]);

        $currentApprovedHours = $hour->status === 'approved' ? (float) $hour->hours : 0.0;
        $this->applyApprovedHourDifference($hour->student_id, $previousApprovedHours, $currentApprovedHours);
        $this->auditTenantActivity($request, 'reviewed hour log', $hour, $oldValues, $hour->fresh()->toArray());

        return redirect()->to($this->tenantRoute($tenant, 'supervisor.dashboard').'#logs')
            ->with('status', 'Student hour log reviewed.');
    }

    public function storeStudent(SubmitOjtHourLogRequest $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();
        /** @var Student|null $student */
        $student = Auth::guard('student')->user();

        abort_unless($tenant && $student, 404);
        $this->authorizeTenantPermission('hours.submit', $tenant);

        $data = $request->validated();

        $hourLog = OjtHourLog::query()->create([
            'student_id' => $student->getKey(),
            'log_date' => $data['log_date'],
            'hours' => $data['hours'],
            'activity' => $data['activity'],
            'status' => 'pending',
            'supervisor_name' => $data['supervisor_name'] ?? null,
            'approved_at' => null,
        ]);
        $this->auditTenantActivity($request, 'submitted hour log', $hourLog, null, $hourLog->toArray());

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

    protected function hourLogPdfPages(string $tenantName, string $generatedAt, array $rows): array
    {
        $pages = [];
        $chunks = array_chunk($rows, 16);

        if ($chunks === []) {
            $chunks = [[]];
        }

        foreach ($chunks as $pageIndex => $chunk) {
            $lines = [
                'OJT HOUR LOGS REPORT',
                'Tenant: '.$tenantName,
                'Generated: '.$generatedAt,
                'Page: '.($pageIndex + 1).' of '.count($chunks),
                str_repeat('-', 110),
                $this->fixedColumns(['Student', 'Program', 'Company', 'Date', 'Hrs', 'Status'], [25, 14, 24, 12, 6, 10]),
                str_repeat('-', 110),
            ];

            if ($chunk === []) {
                $lines[] = 'No OJT hour logs are available.';
            }

            foreach ($chunk as $row) {
                $lines[] = $this->fixedColumns([
                    $row['student_name'],
                    $row['program'],
                    $row['company'],
                    $row['date'],
                    $row['hours'],
                    $row['status'],
                ], [25, 14, 24, 12, 6, 10]);
                $lines[] = 'Student No: '.$row['student_number'].' | Supervisor: '.$row['supervisor'].' | Approved: '.$row['approved_at'].' | Created: '.$row['created_at'];
                $lines[] = 'Activity: '.$this->limitText($row['activity'], 98);
                $lines[] = '';
            }

            $pages[] = $lines;
        }

        return $pages;
    }

    protected function fixedColumns(array $values, array $widths): string
    {
        $columns = [];

        foreach ($values as $index => $value) {
            $width = $widths[$index];
            $columns[] = str_pad($this->limitText((string) $value, $width), $width);
        }

        return implode(' | ', $columns);
    }

    protected function limitText(string $value, int $length): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?: '');

        if (strlen($value) <= $length) {
            return $value;
        }

        return substr($value, 0, max(0, $length - 3)).'...';
    }
}
