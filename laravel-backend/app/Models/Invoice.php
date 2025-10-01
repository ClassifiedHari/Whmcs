<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';
    public $timestamps = false;

    protected $fillable = [
        'userid',
        'invoicenum',
        'date',
        'duedate',
        'datepaid',
        'subtotal',
        'credit',
        'tax',
        'tax2',
        'total',
        'taxrate',
        'taxrate2',
        'status',
        'paymentmethod',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'duedate' => 'date',
        'datepaid' => 'datetime',
        'subtotal' => 'decimal:2',
        'credit' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax2' => 'decimal:2',
        'total' => 'decimal:2',
        'taxrate' => 'decimal:2',
        'taxrate2' => 'decimal:2',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'userid');
    }
}
