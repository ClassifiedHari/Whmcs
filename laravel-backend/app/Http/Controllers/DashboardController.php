<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Service;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function index(): JsonResponse
    {
        $stats = [
            'total_clients' => Client::count(),
            'active_clients' => Client::where('status', 'Active')->count(),
            'total_services' => Service::count(),
            'active_services' => Service::where('status', 'Active')->count(),
            'total_domains' => Domain::count(),
            'active_domains' => Domain::where('status', 'Active')->count(),
            'total_invoices' => Invoice::count(),
            'unpaid_invoices' => Invoice::where('status', 'Unpaid')->count(),
            'total_revenue' => Invoice::where('status', 'Paid')->sum('amount'),
            'pending_tickets' => Ticket::whereIn('status', ['Open', 'In Progress'])->count(),
            'closed_tickets' => Ticket::where('status', 'Closed')->count(),
        ];

        // Recent activities
        $recent_clients = Client::orderBy('id', 'desc')->limit(5)->get();
        $recent_invoices = Invoice::with('client')->orderBy('id', 'desc')->limit(5)->get();
        $recent_tickets = Ticket::with('client')->orderBy('id', 'desc')->limit(5)->get();

        return response()->json([
            'stats' => $stats,
            'recent_clients' => $recent_clients,
            'recent_invoices' => $recent_invoices,
            'recent_tickets' => $recent_tickets,
        ]);
    }
}
