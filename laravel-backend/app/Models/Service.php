<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';
    public $timestamps = false;

    protected $fillable = [
        'userid',
        'packageid',
        'producttype',
        'domain',
        'dedicatedip',
        'serverhostname',
        'regdate',
        'nextduedate',
        'terminationdate',
        'domainstatus',
        'billingcycle',
        'amount',
    ];

    protected $casts = [
        'regdate' => 'date',
        'nextduedate' => 'date',
        'terminationdate' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class, 'userid');
    }
}
