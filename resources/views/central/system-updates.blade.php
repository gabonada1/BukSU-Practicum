@php
    $layoutMode = 'dashboard';
@endphp

@extends('layouts.central')

@section('content')
    @if ($errors->any())
        <div class="error-panel">
            <strong>System update release could not be created.</strong>
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
            <p>Refresh tag records from GitHub before creating or reviewing releases.</p>
        </div>
        <form method="POST" action="{{ $syncTagsAction }}" class="update-sync-form">
            @csrf
            <button type="submit" class="secondary update-sync-button">Sync GitHub Tags</button>
        </form>
    </section>

    <section class="updates-single-column">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Central Releases</span>
                    <h2>Create the next update automatically</h2>
                    <p>Central admin creates the next GitHub tag automatically, publishes it for tenants, and keeps one shared release catalog for the whole platform.</p>
                </div>
                <span class="status-pill">{{ $nextVersion }}</span>
            </div>

            <form method="POST" action="{{ $createReleaseAction }}">
                @csrf
                <label>
                    GitHub Repository
                    <input type="text" value="{{ $repository }}" readonly>
                    <small>The next version tag is created on this repository's default branch.</small>
                </label>
                <label>
                    Next Automatic Version
                    <input type="text" value="{{ $nextVersion }}" readonly>
                </label>
                <label>
                    Release Notes
                    <textarea name="notes" rows="5" placeholder="Summarize what changed in this update for tenants and central administrators.">{{ old('notes') }}</textarea>
                </label>
                <div class="profile-mini-grid">
                    <div class="profile-detail-card">
                        <span>GitHub Tagging</span>
                        <strong>Automatic</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Release Status</span>
                        <strong>Published</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Tenant Visibility</span>
                        <strong>Immediate</strong>
                    </div>
                    <div class="profile-detail-card">
                        <span>Apply Commands</span>
                        <strong>Migrate, Seed, Build</strong>
                    </div>
                </div>
                <div class="actions">
                    <button type="submit">Create Next Update</button>
                </div>
            </form>
        </article>

    </section>

    <section class="section-card">
        <div class="section-header">
            <div>
                <span class="mini-kicker">Published Versions</span>
                <h2>Release Catalog</h2>
                <p>These versions are visible to tenant admins on their update page.</p>
            </div>
        </div>

        @if ($releases->isEmpty())
            <p>No update versions have been published yet.</p>
        @else
            <div class="updates-release-list">
                @foreach ($releases as $release)
                    <div class="updates-release-row">
                        <div>
                            <strong>{{ $release->version }}</strong>
                            <p>{{ $release->github_tag }} &middot; {{ $release->published_at?->format('M d, Y h:i A') ?: 'Published' }}</p>
                            <p>{{ $release->notes ?: 'No release notes were provided for this version.' }}</p>
                        </div>
                        <span class="table-badge">{{ strtoupper($release->status) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
