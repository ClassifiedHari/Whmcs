<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TicketController;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

// Clients
Route::apiResource('clients', ClientController::class);

// Services
Route::apiResource('services', ServiceController::class);

// Domains
Route::apiResource('domains', DomainController::class);

// Invoices
Route::apiResource('invoices', InvoiceController::class);

// Tickets
Route::apiResource('tickets', TicketController::class);
