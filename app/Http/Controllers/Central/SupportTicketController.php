<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralSupportTicketRequest;
use App\Models\SupportTicket;
use App\Support\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(): View
    {
        $tickets = SupportTicket::query()
            ->with(['tenant', 'resolver'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('central.support.index', [
            'pageTitle' => 'Support Tickets | '.config('app.name', 'University Practicum'),
            'tickets' => $tickets,
            'ticketStats' => [
                'open' => SupportTicket::query()->where('status', 'open')->count(),
                'in_progress' => SupportTicket::query()->where('status', 'in_progress')->count(),
                'urgent' => SupportTicket::query()->where('priority', 'urgent')->count(),
                'total' => SupportTicket::query()->count(),
            ],
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(CentralSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validated();

        $oldValues = $ticket->only(['status', 'superadmin_response', 'resolved_by', 'resolved_at']);
        $actor = Auth::guard('central_superadmin')->user();
        $isResolved = in_array($validated['status'], ['resolved', 'closed'], true);

        $ticket->forceFill([
            'status' => $validated['status'],
            'superadmin_response' => $validated['superadmin_response'] ?? null,
            'resolved_by' => $isResolved ? $actor?->getKey() : null,
            'resolved_at' => $isResolved ? now() : null,
        ])->save();

        AuditLogger::log(
            'central_superadmin',
            $actor?->getKey(),
            $actor?->name,
            'updated support ticket',
            $ticket,
            $oldValues,
            $ticket->fresh()->toArray(),
            $request,
        );

        return redirect()
            ->route('central.support.index')
            ->with('status', 'Support ticket updated successfully.');
    }

    protected function statuses(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In progress',
            'waiting_on_tenant' => 'Waiting on tenant',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }
}
