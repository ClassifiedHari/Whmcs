<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';
    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'client_id',
        'subject',
        'status',
        'priority',
        'department',
        'category',
        'assigned_to',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }
}
