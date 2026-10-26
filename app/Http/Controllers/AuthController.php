<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterPaidRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterTrialRequest;
use App\Models\Order;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthController extends Controller
{
    /**
     * Registrasi umum (opsional).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => 'owner',
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil.',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Registrasi akun pemilik kos baru dengan masa trial.
     */
    public function registerTrial(RegisterTrialRequest $request): JsonResponse
    {
        $package = Package::find($request->validated('package_id'));

        if (! $package) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket langganan tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Pengecekan batasan jumlah pintu (max_doors)
        $jumlahPintu = (int) $request->validated('jumlah_pintu');
        if ($package->max_doors !== -1 && $jumlahPintu > $package->max_doors) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jumlah pintu melebihi kapasitas maksimal paket yang dipilih (Maks. '.$package->max_doors.' pintu).',
                'errors' => [
                    'jumlah_pintu' => [
                        'Jumlah pintu melebihi kapasitas maksimal paket '.$package->name.' (Maks. '.$package->max_doors.' pintu).',
                    ],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->validated('name'),
                'nama_kos' => $request->validated('nama_kos'),
                'email' => $request->validated('email'),
                'whatsapp' => $request->validated('whatsapp'),
                'jumlah_pintu' => $jumlahPintu,
                'password' => $request->validated('password'),
                'role' => 'owner',
                'status' => 'active',
                'is_active' => true,
            ]);

            $durationDays = (int) ($package->duration_days ?: 14);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package_name' => $package->name,
                'status' => 'trial',
                'starts_at' => now(),
                'trial_ends_at' => now()->addDays($durationDays),
                'expires_at' => null,
            ]);

            DB::commit();

            $token = auth('api')->login($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi akun trial berhasil.',
                'data' => [
                    'user' => $user,
                    'subscription' => $subscription,
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ],
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Registrasi trial gagal: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Registrasi akun pemilik kos baru langsung memilih paket berbayar (Midtrans Snap).
     */
    public function registerPaid(RegisterPaidRequest $request): JsonResponse
    {
        $package = PackageService::find($request->validated('package_id'));

        if (! $package) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket langganan tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();

        try {
            // 1. Buat user baru dengan status pending_payment dan is_active = false
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'role' => 'owner',
                'status' => 'pending_payment',
                'is_active' => false,
            ]);

            // 2. Generate unique order ID
            $orderId = 'ORDER-PINTUSATU-'.now()->format('YmdHis').'-'.strtoupper(Str::random(5));
            $grossAmount = (int) $package['price'];

            // 3. Request Snap Token ke Midtrans API
            $snap = MidtransService::createSnapTransaction([
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
                'item_details' => [
                    [
                        'id' => (string) $package['id'],
                        'price' => $grossAmount,
                        'quantity' => 1,
                        'name' => substr($package['name'], 0, 50),
                    ],
                ],
            ]);

            // 4. Catat transaksi pembayaran ke tabel orders
            $order = Order::create([
                'user_id' => $user->id,
                'package_id' => $package['id'],
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
                'status' => 'pending',
                'snap_token' => $snap['token'] ?? null,
                'snap_redirect_url' => $snap['redirect_url'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi berhasil. Silakan selesaikan pembayaran paket langganan Anda.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'status' => $user->status,
                        'is_active' => $user->is_active,
                    ],
                    'nama_kos' => $request->validated('nama_kos'),
                    'order' => [
                        'id' => $order->id,
                        'order_id' => $order->order_id,
                        'package_name' => $package['name'],
                        'gross_amount' => $order->gross_amount,
                        'status' => $order->status,
                        'snap_token' => $order->snap_token,
                        'snap_redirect_url' => $order->snap_redirect_url,
                    ],
                ],
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Registrasi pembayaran gagal: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Authenticate user with credentials and return a JWT token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $user = auth('api')->user();

        // Poin 2: User tidak boleh login jika statusnya pending_payment / belum aktif
        if ($user && ($user->status === 'pending_payment' || ! $user->is_active)) {
            auth('api')->logout();

            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum aktif. Silakan selesaikan pembayaran paket langganan Anda terlebih dahulu.',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get the authenticated User.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        if ($user) {
            $user->load('subscription');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data profil berhasil diambil.',
            'data' => [
                'user' => $user,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil. Token telah dinonaktifkan.',
        ], Response::HTTP_OK);
    }
}
