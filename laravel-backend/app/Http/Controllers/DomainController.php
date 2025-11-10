<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DomainController extends Controller
{
    /**
     * Display a listing of domains.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Domain::with('client');

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Client filter
        if ($request->has('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        $domains = $query->orderBy('id', 'desc')->paginate(50);

        return response()->json($domains);
    }

    /**
     * Display the specified domain.
     */
    public function show(int $id): JsonResponse
    {
        $domain = Domain::with('client')->findOrFail($id);

        return response()->json($domain);
    }

    /**
     * Store a newly created domain.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'domain' => 'required|string',
            'registrar' => 'required|string',
            'status' => 'required|string',
            'registration_date' => 'required|date',
            'expiry_date' => 'required|date',
        ]);

        $domain = Domain::create($validated);

        return response()->json($domain, 201);
    }

    /**
     * Update the specified domain.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $domain = Domain::findOrFail($id);

        $validated = $request->validate([
            'domain' => 'sometimes|string',
            'registrar' => 'sometimes|string',
            'status' => 'sometimes|string',
            'expiry_date' => 'sometimes|date',
        ]);

        $domain->update($validated);

        return response()->json($domain);
    }

    /**
     * Remove the specified domain.
     */
    public function destroy(int $id): JsonResponse
    {
        $domain = Domain::findOrFail($id);
        $domain->delete();

        return response()->json(['message' => 'Domain deleted successfully'], 200);
    }
}
