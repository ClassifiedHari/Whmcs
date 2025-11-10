<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    /**
     * Display a listing of clients.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('company', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $clients = $query->orderBy('id', 'desc')->paginate(50);

        return response()->json($clients);
    }

    /**
     * Display the specified client.
     */
    public function show(int $id): JsonResponse
    {
        $client = Client::with(['services', 'domains', 'invoices', 'tickets'])
            ->findOrFail($id);

        return response()->json($client);
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive,Closed',
        ]);

        $validated['registration_date'] = now();

        $client = Client::create($validated);

        return response()->json($client, 201);
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:clients,email,' . $id,
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'sometimes|in:Active,Inactive,Closed',
        ]);

        $client->update($validated);

        return response()->json($client);
    }

    /**
     * Remove the specified client.
     */
    public function destroy(int $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(['message' => 'Client deleted successfully'], 200);
    }
}
