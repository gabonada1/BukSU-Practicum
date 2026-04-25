<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Support\Security\AuditLogger;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantSupportController extends Controller
{
    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $tickets = SupportTicket::query()
            ->where('tenant_id', $tenant->getKey())
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('tenant.support.index', [
            'tenant' => $tenant,
            'pageTitle' => 'Support Tickets | '.$tenant->name,
            'tickets' => $tickets,
            'ticketStats' => [
                'open' => SupportTicket::query()
                    ->where('tenant_id', $tenant->getKey())
                    ->whereIn('status', ['open', 'in_progress', 'waiting_on_tenant'])
                    ->count(),
                'total' => SupportTicket::query()
                    ->where('tenant_id', $tenant->getKey())
                    ->count(),
            ],
            'categories' => $this->categories(),
            'priorities' => $this->priorities(),
            'storeAction' => route('tenant.admin.support.store'),
        ]);
    }

    public function store(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->tenant();

        abort_unless($tenant, 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:40', 'in:account,billing,technical,data,updates,general'],
            'priority' => ['required', 'string', 'max:20', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $actor = Auth::guard('tenant_admin')->user();

        $ticket = SupportTicket::query()->create([
            'tenant_id' => $tenant->getKey(),
            'tenant_name' => $tenant->name,
            'requester_id' => $actor?->getKey(),
            'requester_name' => $actor?->name ?: 'Tenant Admin',
            'requester_email' => $actor?->email ?: 'unknown@example.com',
            ...$validated,
        ]);

        AuditLogger::log(
            'tenant_admin',
            $actor?->getKey(),
            $actor?->name,
            'submitted support ticket',
            $ticket,
            null,
            $ticket->toArray(),
            $request,
        );

        return redirect()
            ->route('tenant.admin.support.index')
            ->with('status', 'Support ticket submitted successfully. Central superadmin can now review it.');
    }

    protected function categories(): array
    {
        return [
            'account' => 'Account access',
            'billing' => 'Billing or subscription',
            'technical' => 'Technical issue',
            'data' => 'Data or records',
            'updates' => 'System updates',
            'general' => 'General support',
        ];
    }

    protected function priorities(): array
    {
        return [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }
}
