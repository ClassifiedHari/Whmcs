<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::with('client');

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Client filter
        if ($request->has('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        $services = $query->orderBy('id', 'desc')->paginate(50);

        return response()->json($services);
    }

    /**
     * Display the specified service.
     */
    public function show(int $id): JsonResponse
    {
        $service = Service::with('client')->findOrFail($id);

        return response()->json($service);
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userid' => 'required|exists:clients,id',
            'domain' => 'required|string',
            'producttype' => 'nullable|string',
            'domainstatus' => 'required|string',
            'billingcycle' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $validated['regdate'] = now();

        $service = Service::create($validated);

        return response()->json($service, 201);
    }

    /**
     * Update the specified service.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'domain' => 'sometimes|string',
            'domainstatus' => 'sometimes|string',
            'billingcycle' => 'sometimes|string',
            'amount' => 'sometimes|numeric',
        ]);

        $service->update($validated);

        return response()->json($service);
    }

    /**
     * Remove the specified service.
     */
    public function destroy(int $id): JsonResponse
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json(['message' => 'Service deleted successfully'], 200);
    }
}
