@php
    $layoutMode = 'dashboard';
@endphp

@extends('layouts.tenant')

@section('content')
    @if ($errors->any())
        <div class="error-panel">
            <strong>Some update actions did not complete.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <section class="updates-toolbar section-card">
        <div>
            <span class="mini-kicker">GitHub Tags</span>
            <h2>Release source</h2>
            <p>Refresh the available GitHub tags before applying a tenant update.</p>
        </div>
        <form method="POST" action="{{ $syncTagsAction }}" class="update-sync-form">
            @csrf
            <button type="submit" class="secondary update-sync-button">Sync GitHub Tags</button>
        </form>
    </section>

    <section class="updates-single-column">
        <article class="section-card">
            @if ($releases->isEmpty())
                <div class="section-header">
                    <div>
                        <span class="mini-kicker">Published Versions</span>
                        <h2>Choose an available update</h2>
                        <p>No published releases are available yet. Ask central administration to create the next update first.</p>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ $applyUpdateAction }}" class="tenant-update-form">
                    @csrf

                    <div class="section-header tenant-update-form-header">
                        <div>
                            <span class="mini-kicker">Published Versions</span>
                            <h2>Choose an available update</h2>
                            <p>Tenant admins see the same published release list as central admin. Select a version below, then apply it with the standard migration, seeding, and build commands.</p>
                        </div>
                        <button type="submit" class="tenant-update-apply-button">Apply Update</button>
                    </div>

                    <div class="updates-release-list">
                        @foreach ($releases as $release)
                            <label class="tenant-update-option">
                                <span class="tenant-update-option-radio">
                                    <input type="radio" name="release_id" value="{{ $release->id }}" @checked((string) old('release_id') === (string) $release->id)>
                                </span>
                                <span class="tenant-update-option-copy">
                                    <span class="tenant-update-option-heading">
                                        <strong>{{ $release->version }}</strong>
                                        <small>{{ $release->github_tag }}</small>
                                    </span>
                                    <span class="tenant-update-option-notes">{{ \Illuminate\Support\Str::limit($release->notes ?: 'No release notes provided.', 48) }}</span>
                                </span>
                                <span class="tenant-update-option-date">
                                    {{ $release->published_at?->format('M d, Y') ?: 'Published' }}<br>
                                    {{ $release->published_at?->format('h:i A') }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    {{ $releases->links() }}
                </form>
            @endif
        </article>

    </section>

    <section class="section-card">
        <div class="section-header">
            <div>
                <span class="mini-kicker">Tenant Update History</span>
                <h2>Recent update runs</h2>
                <p>These runs were started from this tenant workspace.</p>
            </div>
        </div>

        @if ($tenantUpdates->isEmpty())
            <p>No tenant-initiated update runs have been recorded yet.</p>
        @else
            <div class="updates-release-list">
                @foreach ($tenantUpdates as $update)
                    @php
                        $displayError = $update->error_message;

                        if (\Illuminate\Support\Str::contains((string) $displayError, 'ncrypto::CSPRNG(nullptr, 0)')) {
                            $displayError = 'Node.js failed to start on this Windows machine, so npm/build steps could not run.';
                        } else {
                            $displayError = \Illuminate\Support\Str::limit((string) $displayError, 220);
                        }
                    @endphp
                    <div class="updates-release-row">
                        <div>
                            <strong>{{ $update->release_version ?: 'Unversioned update' }}</strong>
                            <p>{{ strtoupper($update->status) }} | {{ $update->created_at?->format('M d, Y h:i A') }}</p>
                            @if ($update->error_message)
                                <p>{{ $displayError }}</p>
                            @endif
                        </div>
                        <span class="table-badge">{{ strtoupper($update->status) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
