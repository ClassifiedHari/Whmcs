<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['client', 'replies']);

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Client filter
        if ($request->has('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        $tickets = $query->orderBy('id', 'desc')->paginate(50);

        return response()->json($tickets);
    }

    /**
     * Display the specified ticket.
     */
    public function show(int $id): JsonResponse
    {
        $ticket = Ticket::with(['client', 'replies'])->findOrFail($id);

        return response()->json($ticket);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userid' => 'required|exists:clients,id',
            'subject' => 'required|string',
            'message' => 'required|string',
            'status' => 'required|string',
            'urgency' => 'nullable|string',
        ]);

        $validated['date'] = now();

        $ticket = Ticket::create($validated);

        return response()->json($ticket, 201);
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|string',
            'urgency' => 'sometimes|string',
        ]);

        $ticket->update($validated);

        return response()->json($ticket);
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully'], 200);
    }
}
