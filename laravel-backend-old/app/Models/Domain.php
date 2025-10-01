<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Domain extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'domain',
        'registrar',
        'status',
        'registration_date',
        'expiry_date',
        'auto_renew',
        'nameservers',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'registration_date' => 'date',
        'expiry_date' => 'date',
        'auto_renew' => 'boolean',
        'nameservers' => 'array',
    ];

    /**
     * Get the client that owns the domain.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get days until expiry.
     */
    protected function daysUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: fn () => now()->diffInDays($this->expiry_date, false),
        );
    }

    /**
     * Scope a query to search domains.
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                  ->orWhere('registrar', 'like', "%{$search}%")
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
     * Scope a query to get expiring domains.
     */
    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('status', 'Active');
    }

    /**
     * Check if domain is expired.
     */
    public function isExpired()
    {
        return $this->expiry_date < now();
    }

    /**
     * Check if domain is expiring soon.
     */
    public function isExpiringSoon($days = 30)
    {
        return $this->expiry_date <= now()->addDays($days) && !$this->isExpired();
    }

    /**
     * Renew domain for specified years.
     */
    public function renew($years = 1)
    {
        $this->expiry_date = $this->expiry_date->addYears($years);
        return $this->save();
    }

    /**
     * Mark expired domains.
     */
    public static function markExpiredDomains()
    {
        return self::where('status', 'Active')
                   ->where('expiry_date', '<', now())
                   ->update(['status' => 'Expired']);
    }
}