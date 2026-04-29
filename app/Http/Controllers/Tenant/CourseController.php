<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesTenantPermissions;
use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Support\Security\AuditLogger;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    use AuthorizesTenantPermissions, InteractsWithTenantRouting;

    public function store(CourseRequest $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.update', $tenant);

        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);

        $course = Course::query()->create($validated);

        $actor = Auth::guard('tenant_admin')->user();

        if ($actor) {
            AuditLogger::log('tenant_admin', $actor->id, $actor->name, 'created', $course, null, $course->toArray(), $request);
        }

        return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show', status: 'Course added successfully.')
            ->withFragment('courses');
    }

    public function update(CourseRequest $request, CurrentTenant $currentTenant, Course $course): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.update', $tenant);

        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);

        $oldValues = $course->toArray();
        $oldHours = number_format((float) $oldValues['required_ojt_hours'], 2, '.', '');
        $newHours = number_format((float) $validated['required_ojt_hours'], 2, '.', '');

        $course->update($validated);

        if ($oldHours !== $newHours) {
            $course->students()
                ->where('required_hours', $oldHours)
                ->update(['required_hours' => $newHours]);
        }

        $actor = Auth::guard('tenant_admin')->user();

        if ($actor) {
            AuditLogger::log('tenant_admin', $actor->id, $actor->name, 'updated', $course, $oldValues, $course->fresh()->toArray(), $request);
        }

        return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show', status: 'Course updated.')
            ->withFragment('courses');
    }

    public function destroy(Request $request, CurrentTenant $currentTenant, Course $course): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        $this->authorizeTenantPermission('user.update', $tenant);

        if ($course->students()->exists()) {
            return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show')
                ->withErrors(['course' => "Cannot delete \"{$course->code}\" because it still has enrolled students. Deactivate it instead."])
                ->withFragment('courses');
        }

        $oldValues = $course->toArray();
        $course->delete();

        $actor = Auth::guard('tenant_admin')->user();

        if ($actor) {
            AuditLogger::log('tenant_admin', $actor->id, $actor->name, 'deleted', $course, $oldValues, null, $request);
        }

        return $this->redirectToTenantRoute($request, $tenant, 'admin.profile.show', status: 'Course removed.')
            ->withFragment('courses');
    }
}
