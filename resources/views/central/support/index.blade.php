@php
    $layoutMode = 'dashboard';
@endphp

@extends('layouts.central')

@section('content')
    @if ($errors->any())
        <div class="error-panel">
            <strong>Support ticket update could not be saved.</strong>
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

    <section class="metric-grid">
        <article class="metric-card">
            <span>Open</span>
            <strong>{{ $ticketStats['open'] }}</strong>
            <small>New requests waiting for first action.</small>
        </article>
        <article class="metric-card">
            <span>In Progress</span>
            <strong>{{ $ticketStats['in_progress'] }}</strong>
            <small>Tickets currently being handled.</small>
        </article>
        <article class="metric-card">
            <span>Urgent</span>
            <strong>{{ $ticketStats['urgent'] }}</strong>
            <small>Highest-priority tenant requests.</small>
        </article>
        <article class="metric-card">
            <span>Total Queue</span>
            <strong>{{ $ticketStats['total'] }}</strong>
            <small>All tenant support tickets.</small>
        </article>
    </section>

    <section class="section-card">
        <div class="section-header">
            <div>
                <span class="mini-kicker">Support Desk</span>
                <h2>Tenant support tickets</h2>
                <p>Review tenant requests, write a response, and update ticket status from one central queue.</p>
            </div>
        </div>

        @if ($tickets->isEmpty())
            <p>No support tickets have been submitted yet.</p>
        @else
            <div class="support-ticket-list">
                @foreach ($tickets as $ticket)
                    <article class="support-ticket-row support-ticket-row-central">
                        <div class="support-ticket-main">
                            <div class="support-ticket-heading">
                                <strong>#{{ $ticket->id }} {{ $ticket->subject }}</strong>
                            </div>
                            <p>{{ $ticket->message }}</p>
                            <div class="support-ticket-details">
                                <span>{{ $ticket->tenant_name }}</span>
                                <span>{{ $ticket->requester_name }} &middot; {{ $ticket->requester_email }}</span>
                                <span>{{ ucfirst($ticket->priority) }} priority</span>
                                <span>{{ ucfirst($ticket->category) }}</span>
                                <span>{{ $ticket->created_at?->format('M d, Y h:i A') }}</span>
                            </div>
                        </div>

                        <details class="support-ticket-action">
                            <summary class="support-view-button" aria-label="View support ticket #{{ $ticket->id }}">View</summary>

                            <form class="support-ticket-form" method="POST" action="{{ route('central.support.update', $ticket) }}">
                                @csrf
                                @method('PATCH')

                                <div class="support-ticket-form-header">
                                    <div>
                                        <h3>Ticket Action</h3>
                                        <p>Update status and response for #{{ $ticket->id }} {{ $ticket->subject }}.</p>
                                    </div>
                                </div>
                                <div class="support-ticket-form-body">
                                    <div class="support-status-field">
                                        <span>Set Status</span>
                                        <div class="support-status-options">
                                            @foreach ($statuses as $value => $label)
                                                <label class="support-status-option">
                                                    <input type="radio" name="status" value="{{ $value }}" @checked(old('status', $ticket->status) === $value) required>
                                                    <span>{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <label>
                                        Superadmin Response
                                        <textarea name="superadmin_response" rows="4" placeholder="Reply or internal resolution note for the tenant.">{{ old('superadmin_response', $ticket->superadmin_response) }}</textarea>
                                    </label>
                                    <div class="actions">
                                        <button type="submit">Save Response</button>
                                    </div>
                                </div>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>

            @if ($tickets->hasPages())
                <div class="pagination">
                    {{ $tickets->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
