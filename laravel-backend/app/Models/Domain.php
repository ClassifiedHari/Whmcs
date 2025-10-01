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
        'client_id',
        'domain',
        'registrar',
        'status',
        'registration_date',
        'expiry_date',
        'auto_renew',
        'nameservers',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'expiry_date' => 'date',
        'auto_renew' => 'boolean',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
