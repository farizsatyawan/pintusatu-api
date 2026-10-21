<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\SubscribeRequest;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    /**
     * Menampilkan daftar pilihan paket langganan yang tersedia.
     */
    public function packages(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Daftar paket langganan berhasil diambil.',
            'data' => PackageService::all(),
        ], Response::HTTP_OK);
    }

    /**
     * Membuat paket langganan baru.
     */
    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = Package::create([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'price' => $request->validated('price'),
            'duration_days' => $request->validated('duration_days'),
            'description' => $request->validated('description'),
            'is_active' => $request->validated('is_active', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Paket langganan berhasil dibuat.',
            'data' => $package,
        ], Response::HTTP_CREATED);
    }

    /**
     * Memproses pembelian atau perpanjangan paket langganan bagi user yang sedang login.
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $package = PackageService::find($request->validated('package_id'));

        if (! $package) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket langganan tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = auth('api')->user();

        DB::beginTransaction();

        try {
            $subscription = $user->subscription;
            $now = now();

            // Jika status masih aktif dan expires_at masih di masa depan, perpanjang dari tanggal kedaluwarsa tersebut
            $baseDate = ($subscription && $subscription->status === 'active' && $subscription->expires_at && $subscription->expires_at->isFuture())
                ? $subscription->expires_at
                : $now;

            $newExpiresAt = (clone $baseDate)->addDays($package['duration_days']);

            if (! $subscription) {
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'package_name' => $package['name'],
                    'status' => 'active',
                    'starts_at' => $now,
                    'trial_ends_at' => null,
                    'expires_at' => $newExpiresAt,
                ]);
            } else {
                $subscription->update([
                    'package_name' => $package['name'],
                    'status' => 'active',
                    'trial_ends_at' => null,
                    'expires_at' => $newExpiresAt,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Paket langganan berhasil diperbarui menjadi '.$package['name'].'.',
                'data' => [
                    'subscription' => $subscription->fresh(),
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses langganan: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
