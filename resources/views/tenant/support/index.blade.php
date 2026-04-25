@php
    $layoutMode = 'dashboard';
@endphp

@extends('layouts.tenant')

@section('content')
    @if ($errors->any())
        <div class="error-panel">
            <strong>Support ticket could not be submitted.</strong>
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

    <section class="updates-single-column">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <span class="mini-kicker">Support Desk</span>
                    <h2>Send a ticket to central admin</h2>
                    <p>Report access, billing, update, data, or technical concerns directly to the superadmin queue.</p>
                </div>
            </div>

            <form method="POST" action="{{ $storeAction }}">
                @csrf

                <label>
                    Subject
                    <input type="text" name="subject" value="{{ old('subject') }}" maxlength="160" placeholder="Briefly describe the concern" required>
                </label>

                <div class="form-grid">
                    <label>
                        Category
                        <select name="category" required>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Priority
                        <select name="priority" required>
                            @foreach ($priorities as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label>
                    Message
                    <textarea name="message" rows="8" maxlength="4000" placeholder="Include what happened, when it started, and any affected users or pages." required>{{ old('message') }}</textarea>
                </label>

                <div class="actions">
                    <button type="submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        Submit Ticket
                    </button>
                </div>
            </form>
        </article>

    </section>

    <section class="section-card">
        <div class="section-header">
            <div>
                <span class="mini-kicker">Ticket History</span>
                <h2>Previous requests</h2>
                <p>Track the latest status and superadmin response for tickets submitted by this tenant.</p>
            </div>
        </div>

        @if ($tickets->isEmpty())
            <p>No support tickets have been submitted yet.</p>
        @else
            <div class="support-ticket-list">
                @foreach ($tickets as $ticket)
                    <article class="support-ticket-row">
                        <div class="support-ticket-main">
                            <div class="support-ticket-heading">
                                <strong>#{{ $ticket->id }} {{ $ticket->subject }}</strong>
                                <span class="table-badge">{{ str_replace('_', ' ', strtoupper($ticket->status)) }}</span>
                            </div>
                            <p>{{ $ticket->message }}</p>
                            @if ($ticket->superadmin_response)
                                <div class="support-response-box">
                                    <span>Superadmin response</span>
                                    <p>{{ $ticket->superadmin_response }}</p>
                                </div>
                            @endif
                        </div>
                        <aside class="support-ticket-meta">
                            <span>{{ ucfirst($ticket->priority) }} priority</span>
                            <span>{{ ucfirst($ticket->category) }}</span>
                            <span>{{ $ticket->created_at?->format('M d, Y h:i A') }}</span>
                        </aside>
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
