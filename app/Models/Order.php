<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'package_id',
    'order_id',
    'gross_amount',
    'status',
    'snap_token',
    'snap_redirect_url',
])]
class Order extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
        ];
    }

    /**
     * Relasi ke User pemilik order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Paket langganan yang dipesan.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
