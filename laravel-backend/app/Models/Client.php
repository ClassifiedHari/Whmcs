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
        'firstname',
        'lastname',
        'companyname',
        'email',
        'address1',
        'address2',
        'city',
        'state',
        'postcode',
        'country',
        'phonenumber',
        'status',
        'datecreated',
        'currency',
        'credit',
        'language',
    ];

    protected $casts = [
        'datecreated' => 'datetime',
        'credit' => 'decimal:2',
    ];

    // Relationships
    public function services()
    {
        return $this->hasMany(Service::class, 'userid');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class, 'userid');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'userid');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'userid');
    }
}
