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
        'tid',
        'userid',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'urgency',
        'date',
        'lastreply',
        'admin',
        'attachment',
    ];

    protected $casts = [
        'date' => 'datetime',
        'lastreply' => 'datetime',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'userid');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }
}
