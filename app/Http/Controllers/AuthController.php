<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterPaidRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterTrialRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
     * Registrasi akun pemilik kos baru dengan masa trial 14 hari.
     */
    public function registerTrial(RegisterTrialRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'role' => 'owner', // Hardcode role 'owner'
            ]);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package_name' => 'Trial 14 Hari',
                'status' => 'trial',
                'starts_at' => now(),
                'trial_ends_at' => now()->addDays(14),
                'expires_at' => null,
            ]);

            DB::commit();

            $token = auth('api')->login($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi akun trial berhasil.',
                'data' => [
                    'user' => $user,
                    'nama_kos' => $request->validated('nama_kos'),
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
     * Registrasi akun pemilik kos baru langsung memilih paket berbayar.
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
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'role' => 'owner', // Hardcode role 'owner'
            ]);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package_name' => $package['name'],
                'status' => 'active',
                'starts_at' => now(),
                'trial_ends_at' => null,
                'expires_at' => now()->addDays($package['duration_days']),
            ]);

            DB::commit();

            $token = auth('api')->login($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi paket berbayar ('.$package['name'].') berhasil.',
                'data' => [
                    'user' => $user,
                    'nama_kos' => $request->validated('nama_kos'),
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
                'message' => 'Registrasi berbayar gagal: '.$e->getMessage(),
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

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'user' => auth('api')->user(),
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
