<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';
    public $timestamps = false;

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

    protected $casts = [
        'registration_date' => 'date',
        'next_due_date' => 'date',
        'recurring_amount' => 'decimal:2',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
