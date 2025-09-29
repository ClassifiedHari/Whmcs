<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'client_id',
        'amount',
        'status',
        'due_date',
        'issue_date',
        'description',
        'payment_method',
        'items',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'issue_date' => 'datetime',
        'items' => 'array',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_id)) {
                $invoice->invoice_id = 'INV-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
            if (empty($invoice->issue_date)) {
                $invoice->issue_date = now();
            }
        });

        static::updated(function (Invoice $invoice) {
            if ($invoice->wasChanged('status') && $invoice->status === 'Paid') {
                $invoice->client->updateStats();
            }
        });
    }

    /**
     * Get the client that owns the invoice.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to search invoices.
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        return $query;
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope a query to filter by client.
     */
    public function scopeForClient($query, $clientId)
    {
        if ($clientId) {
            $query->where('client_id', $clientId);
        }
        return $query;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue()
    {
        return $this->status === 'Pending' && $this->due_date < now();
    }

    /**
     * Mark overdue invoices.
     */
    public static function markOverdueInvoices()
    {
        return self::where('status', 'Pending')
                   ->where('due_date', '<', now())
                   ->update(['status' => 'Overdue']);
    }
}