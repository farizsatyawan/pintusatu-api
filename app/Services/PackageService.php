<?php

namespace App\Services;

use App\Models\Package;

class PackageService
{
    /**
     * Get all active packages from database.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return Package::where('is_active', true)->get()->toArray();
    }

    /**
     * Find a package by integer ID or name from database.
     *
     * @return array<string, mixed>|null
     */
    public static function find(string|int $packageId): ?array
    {
        $query = Package::where('is_active', true);

        if (is_numeric($packageId)) {
            $query->where('id', (int) $packageId);
        } else {
            $query->where(function ($q) use ($packageId) {
                $q->where('id', $packageId)->orWhere('name', (string) $packageId);
            });
        }

        $pkg = $query->first();

        return $pkg ? $pkg->toArray() : null;
    }

    /**
     * Check if a package exists in database.
     */
    public static function exists(string|int $packageId): bool
    {
        return self::find($packageId) !== null;
    }
}
