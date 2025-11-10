<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory;

    protected $table = 'ticket_replies';
    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'userid',
        'admin',
        'message',
        'date',
        'attachment',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
