<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with('client');

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Client filter
        if ($request->has('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        $invoices = $query->orderBy('id', 'desc')->paginate(50);

        return response()->json($invoices);
    }

    /**
     * Display the specified invoice.
     */
    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with('client')->findOrFail($id);

        return response()->json($invoice);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'due_date' => 'required|date',
            'issue_date' => 'required|date',
            'amount' => 'required|numeric',
            'status' => 'required|string',
            'payment_method' => 'nullable|string',
        ]);

        $invoice = Invoice::create($validated);

        return response()->json($invoice, 201);
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|string',
            'payment_method' => 'sometimes|string',
        ]);

        $invoice->update($validated);

        return response()->json($invoice);
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully'], 200);
    }
}
