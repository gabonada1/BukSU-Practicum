<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithTenantRouting;
use App\Models\SystemRelease;
use App\Models\SystemUpdate;
use App\Services\GithubReleaseManager;
use App\Support\Tenancy\CurrentTenant;
use App\Services\SystemUpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class TenantReleaseController extends Controller
{
    use InteractsWithTenantRouting;

    public function __construct(
        protected SystemUpdateService $systemUpdateService,
        protected GithubReleaseManager $githubReleaseManager,
    ) {
    }

    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        abort_unless(Auth::guard('tenant_admin')->check(), 403);

        rescue(fn () => $this->githubReleaseManager->syncPublishedTags(), null, false);

        $releases = SystemRelease::query()
            ->where('status', 'published')
            ->latest('published_at')
            ->latest()
            ->paginate(5, ['*'], 'releases_page')
            ->withQueryString();

        $tenantUpdates = SystemUpdate::query()
            ->latest()
            ->get()
            ->filter(fn (SystemUpdate $update) => (int) data_get($update->options, 'tenant_id') === (int) $tenant->getKey())
            ->take(10)
            ->values();

        return view('tenant.updates.index', [
            'tenant' => $tenant,
            'pageTitle' => 'Updates | '.data_get($tenant->settings, 'branding.portal_title', config('app.name', 'University Practicum')),
            'releases' => $releases,
            'applyUpdateAction' => $this->tenantRoute($tenant, 'admin.updates.apply'),
            'syncTagsAction' => $this->tenantRoute($tenant, 'admin.updates.sync-tags'),
            'tenantUpdates' => $tenantUpdates,
            'currentVersion' => data_get($tenant->settings, 'release_preferences.preferred_release_version', config('app.version', '1.0.0')),
            'repository' => config('services.github.repository'),
        ]);
    }

    public function syncTags(CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        abort_unless(Auth::guard('tenant_admin')->check(), 403);

        try {
            $synced = $this->githubReleaseManager->syncPublishedTags();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->to($this->tenantRoute($tenant, 'admin.updates.index'))
                ->withErrors(['release_id' => $exception->getMessage()]);
        }

        return redirect()->to($this->tenantRoute($tenant, 'admin.updates.index'))
            ->with('status', "{$synced} GitHub tag(s) were synced into the release catalog.");
    }

    public function apply(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);
        abort_unless(Auth::guard('tenant_admin')->check(), 403);

        $validated = $request->validate([
            'release_id' => ['required', 'integer', 'exists:central.system_releases,id'],
        ]);

        $release = SystemRelease::query()
            ->where('status', 'published')
            ->findOrFail($validated['release_id']);

        $admin = Auth::guard('tenant_admin')->user();

        $update = SystemUpdate::query()->create([
            'release_version' => $release->version,
            'release_url' => $release->archive_url,
            'notes' => $release->notes,
            'status' => 'pending',
            'options' => [
                'backup_current_release' => true,
                'run_npm_build' => true,
                'run_migrations' => true,
                'run_seeders' => true,
                'tenant_id' => $tenant->getKey(),
                'tenant_name' => $tenant->name,
                'initiated_by' => 'tenant_admin',
                'initiated_by_name' => $admin?->name,
                'system_release_id' => $release->getKey(),
            ],
            'logs' => [],
            'triggered_by' => null,
        ]);

        try {
            $this->systemUpdateService->run($update);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'release_id' => 'The tenant update could not be applied. Check the update history on this page for details.',
            ]);
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $settings['release_preferences'] = [
            'preferred_release_id' => $release->getKey(),
            'preferred_release_version' => $release->version,
            'preferred_release_tag' => $release->github_tag,
        ];

        $tenant->update([
            'settings' => $settings,
        ]);

        return redirect()->to($this->tenantRoute($tenant, 'admin.updates.index'))
            ->with('status', "Update {$release->version} was applied with migrations, database seeders, and frontend build.");
    }
}
