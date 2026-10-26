<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    /**
     * Request token Snap dan URL pembayaran dari API Midtrans.
     *
     * @param array<string, mixed> $payload
     * @return array{token: string, redirect_url: string}
     * @throws Exception
     */
    public static function createSnapTransaction(array $payload): array
    {
        $serverKey = config('midtrans.server_key');
        $snapUrl = config('midtrans.snap_url');

        // Fitur Mock Mode untuk testing lokal jika Server Key belum aktif
        if (config('midtrans.mock', false) || env('MIDTRANS_MOCK', false)) {
            $mockToken = 'MOCK-SNAP-' . strtoupper(bin2hex(random_bytes(8)));
            return [
                'token' => $mockToken,
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $mockToken,
            ];
        }

        if (empty($serverKey)) {
            throw new Exception('MIDTRANS_SERVER_KEY belum dikonfigurasi di file .env backend.');
        }

        try {
            $response = Http::withBasicAuth(trim($serverKey), '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(15)
                ->post($snapUrl, $payload);

            if ($response->failed()) {
                Log::error('Midtrans Snap API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);

                $status = $response->status();
                $body = $response->json();
                $errorDetail = $body['error_messages'][0] ?? $body['error'] ?? $body['message'] ?? $response->body();

                if ($status === 401) {
                    $errorMessage = "Kredensial Midtrans 401 Unauthorized: MIDTRANS_SERVER_KEY di .env tidak valid atau sudah kedaluwarsa di dashboard Midtrans Sandbox.";
                } else {
                    $errorMessage = "Midtrans Snap Error (HTTP {$status}): {$errorDetail}";
                }

                throw new Exception($errorMessage);
            }

            $data = $response->json();

            return [
                'token' => $data['token'] ?? '',
                'redirect_url' => $data['redirect_url'] ?? '',
            ];
        } catch (Exception $e) {
            Log::error('Midtrans Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifikasi keaslian signature key dari notifikasi webhook Midtrans.
     * Formula: SHA512(order_id + status_code + gross_amount + ServerKey)
     */
    public static function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = config('midtrans.server_key');
        
        // Midtrans mengirimkan gross_amount dengan format string (cth: "229000.00" atau "229000")
        $computed = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($computed, $signatureKey);
    }
}
