<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'floor',
        'room_size',
        'price',
        'status',
        'image_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'price' => 'integer',
        ];
    }

    /**
     * Relasi ke tipe kamar.
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Relasi ke riwayat sewa kamar.
     */
    public function leases(): HasMany
    {
        return $this->hasMany(RoomLease::class);
    }

    /**
     * Relasi ke sewa kamar yang sedang aktif.
     */
    public function activeLease(): HasOne
    {
        return $this->hasOne(RoomLease::class)->where('status', 'active');
    }
}
