<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    protected $table = 'domains';
    public $timestamps = false;

    protected $fillable = [
        'userid',
        'domain',
        'registrar',
        'registrationdate',
        'expirydate',
        'status',
        'subscriptionid',
        'promoid',
        'recurringamount',
    ];

    protected $casts = [
        'registrationdate' => 'date',
        'expirydate' => 'date',
        'recurringamount' => 'decimal:2',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'userid');
    }
}
