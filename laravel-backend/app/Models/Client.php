<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';
    public $timestamps = false;

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

    protected $casts = [
        'registration_date' => 'date',
        'last_login' => 'datetime',
        'total_spent' => 'decimal:2',
        'active_services' => 'integer',
    ];

    // Relationships
    public function services()
    {
        return $this->hasMany(Service::class, 'client_id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class, 'client_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'client_id');
    }
}
