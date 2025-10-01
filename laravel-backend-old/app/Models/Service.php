<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'product_name',
        'domain',
        'status',
        'next_due_date',
        'recurring_amount',
        'billing_cycle',
        'registration_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'next_due_date' => 'date',
        'recurring_amount' => 'decimal:2',
        'registration_date' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Service $service) {
            if (empty($service->registration_date)) {
                $service->registration_date = now();
            }
        });

        static::updated(function (Service $service) {
            if ($service->wasChanged('status')) {
                $service->client->updateStats();
            }
        });
    }

    /**
     * Get the client that owns the service.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to search services.
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%")
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
     * Check if service is due soon.
     */
    public function isDueSoon($days = 7)
    {
        return $this->next_due_date <= now()->addDays($days);
    }

    /**
     * Get services due for renewal.
     */
    public static function getDueForRenewal($days = 7)
    {
        return self::where('status', 'Active')
                   ->where('next_due_date', '<=', now()->addDays($days))
                   ->with('client')
                   ->get();
    }
}