<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_id)) {
                $ticket->ticket_id = 'TKT-' . strtoupper(Str::random(6));
            }
        });
    }

    /**
     * Get the client that owns the ticket.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get all replies for the ticket.
     */
    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * Get the latest reply.
     */
    public function latestReply()
    {
        return $this->hasOne(TicketReply::class)->latest();
    }

    /**
     * Scope a query to search tickets.
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_id', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
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
     * Scope a query to filter by priority.
     */
    public function scopePriority($query, $priority)
    {
        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }
        return $query;
    }

    /**
     * Scope for open tickets.
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['Open', 'In Progress']);
    }

    /**
     * Add a reply to the ticket.
     */
    public function addReply($message, $sender, $isAdmin = false)
    {
        return $this->replies()->create([
            'message' => $message,
            'sender' => $sender,
            'is_admin' => $isAdmin,
        ]);
    }
}