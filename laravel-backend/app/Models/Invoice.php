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

    protected $casts = [
        'due_date' => 'date',
        'issue_date' => 'date',
        'amount' => 'decimal:2',
        'items' => 'array',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
