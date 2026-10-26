<?php

namespace App\Models;

use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'category',
    'max_doors',
    'door_label',
    'price',
    'duration_days',
    'billing_cycle',
    'badge_text',
    'discount_label',
    'is_featured',
    'description',
    'features',
    'is_active',
])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_doors' => 'integer',
            'price' => 'integer',
            'duration_days' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'features' => 'array',
        ];
    }

    /**
     * Relasi ke semua order yang memilih paket ini.
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }
}
