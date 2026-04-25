<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\SystemRelease;
use App\Services\GithubReleaseManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SystemUpdateController extends Controller
{
    public function __construct(
        protected GithubReleaseManager $githubReleaseManager,
    ) {
    }

    public function index(): View
    {
        rescue(fn () => $this->githubReleaseManager->syncPublishedTags(), null, false);

        $releases = SystemRelease::query()
            ->latest('published_at')
            ->latest()
            ->get();

        $nextVersion = rescue(
            fn () => $this->githubReleaseManager->nextVersion(),
            config('app.version', '1.0.0'),
            false
        );

        return view('central.system-updates', [
            'pageTitle' => 'System Updates | '.config('app.name', 'University Practicum'),
            'releases' => $releases,
            'nextVersion' => $nextVersion,
            'repository' => rescue(fn () => $this->githubReleaseManager->repository(), config('services.github.repository')),
            'createReleaseAction' => route('central.updates.store'),
            'syncTagsAction' => route('central.updates.sync-tags'),
        ]);
    }

    public function syncTags(): RedirectResponse
    {
        try {
            $synced = $this->githubReleaseManager->syncPublishedTags();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('central.updates.index')
                ->withErrors(['release' => $exception->getMessage()]);
        }

        return redirect()->route('central.updates.index')
            ->with('status', "{$synced} GitHub tag(s) were synced into the release catalog.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $release = $this->githubReleaseManager->createAutomaticTag($validated['notes'] ?? null);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('central.updates.index')
                ->withErrors(['release' => $exception->getMessage()]);
        }

        SystemRelease::query()->create([
            'version' => $release['version'],
            'github_tag' => $release['github_tag'],
            'github_sha' => $release['github_sha'],
            'archive_url' => $release['archive_url'],
            'notes' => $release['notes'],
            'status' => 'published',
            'created_by' => Auth::guard('central_superadmin')->id(),
            'published_at' => now(),
        ]);

        return redirect()->route('central.updates.index')
            ->with('status', "Release {$release['version']} was created, tagged on GitHub, and published for tenants.");
    }
}
