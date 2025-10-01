<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'company',
        'phone',
        'address',
        'status',
        'registration_date',
        'last_login',
        'total_spent',
        'active_services',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'registration_date' => 'datetime',
        'last_login' => 'datetime',
        'total_spent' => 'decimal:2',
        'active_services' => 'integer',
    ];

    /**
     * Get the client's full name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name . ' ' . $this->last_name,
        );
    }

    /**
     * Get all invoices for the client.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all services for the client.
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all domains for the client.
     */
    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get all tickets for the client.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get active services count.
     */
    public function getActiveServicesCountAttribute()
    {
        return $this->services()->where('status', 'Active')->count();
    }

    /**
     * Scope a query to search clients.
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
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
     * Update client statistics.
     */
    public function updateStats()
    {
        $this->total_spent = $this->invoices()->where('status', 'Paid')->sum('amount');
        $this->active_services = $this->services()->where('status', 'Active')->count();
        $this->save();
    }
}