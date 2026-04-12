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

    <section class="content-grid" style="grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Published Versions</span>
                    <h2>Choose an available update</h2>
                    <p>Tenant admins see the same published release list as central admin. Select a version below, then apply it with the standard update commands.</p>
                </div>
            </div>

            @if ($releases->isEmpty())
                <p>No published releases are available yet. Ask central administration to create the next update first.</p>
            @else
                <form method="POST" action="{{ $applyUpdateAction }}">
                    @csrf

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

                    <div class="actions">
                        <button type="submit">Apply Update</button>
                    </div>
                </form>
            @endif
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Automatic Commands</span>
                    <h2>What runs on apply</h2>
                    <p>This action downloads the selected GitHub tag archive and runs the common deployment steps automatically.</p>
                </div>
            </div>

            <div class="profile-mini-grid">
                <div class="profile-detail-card">
                    <span>Repository</span>
                    <strong>{{ $repository }}</strong>
                </div>
                <div class="profile-detail-card">
                    <span>Current Display Version</span>
                    <strong>{{ $currentVersion }}</strong>
                </div>
                <div class="profile-detail-card">
                    <span>Composer Install</span>
                    <strong>Enabled</strong>
                </div>
                <div class="profile-detail-card">
                    <span>NPM Install</span>
                    <strong>Enabled</strong>
                </div>
                <div class="profile-detail-card">
                    <span>NPM Build</span>
                    <strong>Enabled</strong>
                </div>
                <div class="profile-detail-card">
                    <span>Migrations</span>
                    <strong>Enabled</strong>
                </div>
            </div>
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
