<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MidtransController extends Controller
{
    /**
     * Menangani callback webhook notifikasi pembayaran dari server Midtrans.
     */
    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans Notification Received', $payload);

        $orderId = $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signatureKey = (string) $request->input('signature_key');
        $transactionStatus = (string) $request->input('transaction_status');
        $fraudStatus = (string) $request->input('fraud_status');

        if (empty($orderId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter order_id tidak ditemukan.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // 1. Verifikasi Signature Key Midtrans
        if (! MidtransService::verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans Notification Invalid Signature', [
                'order_id' => $orderId,
                'signature_key' => $signatureKey,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Signature key tidak valid.',
            ], Response::HTTP_FORBIDDEN);
        }

        // 2. Cari data Order
        $order = Order::where('order_id', $orderId)->first();

        if (! $order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        // 3. Update status Order, User, dan Subscription dalam Database Transaction
        DB::beginTransaction();

        try {
            $user = User::find($order->user_id);
            $package = Package::find($order->package_id);

            // Kondisi status pembayaran sukses (settlement, capture accept)
            $isSuccess = ($transactionStatus === 'settlement') ||
                ($transactionStatus === 'capture' && $fraudStatus === 'accept') ||
                ($transactionStatus === 'success');

            if ($isSuccess) {
                // A. Update Order menjadi 'paid'
                $order->update([
                    'status' => 'paid',
                ]);

                // B. Update User menjadi aktif
                if ($user) {
                    $user->update([
                        'status' => 'active',
                        'is_active' => true,
                    ]);

                    // C. Buat / Perpanjang Subscription user
                    if ($package) {
                        $now = now();
                        $durationDays = (int) ($package->duration_days ?: 30);
                        $expiresAt = (clone $now)->addDays($durationDays);

                        Subscription::create([
                            'user_id' => $user->id,
                            'package_name' => $package->name,
                            'status' => 'active',
                            'starts_at' => $now,
                            'trial_ends_at' => null,
                            'expires_at' => $expiresAt,
                        ]);
                    }
                }

                Log::info('Midtrans Payment Success', ['order_id' => $orderId, 'user_id' => $user?->id]);
            } elseif (in_array($transactionStatus, ['cancel', 'deny'])) {
                $order->update(['status' => 'cancelled']);
            } elseif ($transactionStatus === 'expire') {
                $order->update(['status' => 'expired']);
            } elseif ($transactionStatus === 'pending') {
                $order->update(['status' => 'pending']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Notifikasi Midtrans berhasil diproses.',
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Midtrans Notification Error: ' . $e->getMessage(), [
                'order_id' => $orderId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses notifikasi: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
