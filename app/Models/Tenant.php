<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'emergency_contact',
    ];

    /**
     * Relasi ke semua kontrak sewa penghuni.
     */
    public function leases(): HasMany
    {
        return $this->hasMany(RoomLease::class);
    }
}

