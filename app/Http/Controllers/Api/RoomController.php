<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomLease;
use App\Models\RoomType;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RoomController extends Controller
{
    /**
     * Menampilkan ringkasan statistik dan daftar kamar dengan filter serta pagination.
     */
    public function index(Request $request): JsonResponse
    {
        // Jika parameter ?id=... dikirim pada GET /api/rooms, langsung tampilkan detail kamar tersebut
        if ($request->filled('id')) {
            return $this->show($request, $request->query('id'));
        }

        // 1. Ringkasan Statistik
        $totalKamar = Room::count();
        $terisi = Room::where('status', 'terisi')->count();
        $kosong = Room::where('status', 'tersedia')->count();
        $okupansi = $totalKamar > 0 ? round(($terisi / $totalKamar) * 100, 1) : 0;

        // 2. Query Daftar Kamar + Eager Loading
        $query = Room::with(['roomType', 'activeLease.tenant']);

        // Filter: status ('tersedia' / 'terisi')
        if ($request->filled('status') && in_array($request->status, ['tersedia', 'terisi'])) {
            $query->where('status', $request->status);
        }

        // Filter: floor
        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        // Filter: room_type_id
        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Filter: search (nomor kamar atau nama penghuni aktif)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('room_number', 'like', "%{$search}%")
                    ->orWhereHas('activeLease.tenant', function ($tenantQuery) use ($search) {
                        $tenantQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $rooms = $query->orderBy('room_number')->paginate(9);

        return response()->json([
            'status' => 'success',
            'message' => 'Data kamar berhasil diambil.',
            'statistics' => [
                'total_kamar' => $totalKamar,
                'terisi' => $terisi,
                'kosong' => $kosong,
                'okupansi' => $okupansi,
            ],
            'data' => $rooms,
        ], Response::HTTP_OK);
    }

    /**
     * Menampilkan detail satu kamar lengkap beserta relasinya.
     */
    public function show(Request $request, $id = null): JsonResponse
    {
        $roomId = $request->query('id') ?: $id;
        $room = Room::with(['roomType', 'activeLease.tenant', 'leases.tenant'])->find($roomId);

        if (! $room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamar tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail kamar berhasil diambil.',
            'data' => $room,
        ], Response::HTTP_OK);
    }

    /**
     * Membuat unit kamar baru (POST /api/rooms).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:50|unique:rooms,room_number',
            'floor' => 'required|integer',
            'room_size' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|in:tersedia,terisi',
            'image_url' => 'nullable|string|max:255',
        ]);

        $room = Room::create([
            'room_type_id' => $validated['room_type_id'],
            'room_number' => $validated['room_number'],
            'floor' => $validated['floor'],
            'room_size' => $validated['room_size'],
            'price' => $validated['price'],
            'status' => $validated['status'] ?? 'tersedia',
            'image_url' => $validated['image_url'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kamar '.$room->room_number.' berhasil dibuat.',
            'data' => $room->load('roomType'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Memperbarui data kamar (PUT /api/rooms/{id}).
     */
    public function update(Request $request, $id = null): JsonResponse
    {
        $roomId = $request->query('id') ?: $id;
        $room = Room::find($roomId);

        if (! $room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamar tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'room_type_id' => 'sometimes|required|exists:room_types,id',
            'room_number' => 'sometimes|required|string|max:50|unique:rooms,room_number,'.$room->id,
            'floor' => 'sometimes|required|integer',
            'room_size' => 'sometimes|required|string|max:50',
            'price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|nullable|in:tersedia,terisi',
            'image_url' => 'sometimes|nullable|string|max:255',
        ]);

        $room->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kamar '.$room->room_number.' berhasil diperbarui.',
            'data' => $room->fresh(['roomType', 'activeLease.tenant']),
        ], Response::HTTP_OK);
    }

    /**
     * Menghapus unit kamar (DELETE /api/rooms/{id}).
     */
    public function destroy(Request $request, $id = null): JsonResponse
    {
        $roomId = $request->query('id') ?: $id;
        $room = Room::find($roomId);

        if (! $room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamar tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $room->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kamar berhasil dihapus.',
        ], Response::HTTP_OK);
    }

    /**
     * Menambahkan penghuni ke kamar (Check-in / Assign Tenant).
     */
    public function assignTenant(Request $request, $id = null): JsonResponse
    {
        $roomId = $request->query('id') ?: $id;
        $room = Room::find($roomId);

        if (! $room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamar tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($room->status === 'terisi') {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamar sedang terisi. Silakan checkout penghuni sebelumnya terlebih dahulu.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'emergency_contact' => 'nullable|string|max:30',
            'rent_price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:bulanan,tahunan',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $lease = DB::transaction(function () use ($room, $validated) {
                // Cari atau buat tenant berdasarkan nomor telepon
                $tenant = Tenant::firstOrCreate(
                    ['phone' => $validated['phone']],
                    [
                        'name' => $validated['name'],
                        'emergency_contact' => $validated['emergency_contact'] ?? null,
                    ]
                );

                // Jika tenant sudah ada sebelumnya namun namanya diupdate
                if ($tenant->name !== $validated['name']) {
                    $tenant->update([
                        'name' => $validated['name'],
                        'emergency_contact' => $validated['emergency_contact'] ?? $tenant->emergency_contact,
                    ]);
                }

                // Buat kontrak sewa baru dengan status 'active'
                $lease = RoomLease::create([
                    'room_id' => $room->id,
                    'tenant_id' => $tenant->id,
                    'rent_price' => $validated['rent_price'],
                    'billing_period' => $validated['billing_period'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'status' => 'active',
                ]);

                // Update status kamar menjadi 'terisi'
                $room->update(['status' => 'terisi']);

                return $lease;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Penghuni berhasil ditambahkan ke kamar '.$room->room_number.'.',
                'data' => [
                    'room' => $room->fresh(['roomType', 'activeLease.tenant']),
                    'lease' => $lease->load('tenant'),
                ],
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menetapkan penghuni: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Melakukan checkout penghuni dari kamar.
     */
    public function checkoutTenant(Request $request, $id = null): JsonResponse
    {
        $roomId = $request->query('id') ?: $id;
        $room = Room::with('activeLease')->find($roomId);

        if (! $room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamar tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            DB::transaction(function () use ($room) {
                // Ubah status room_leases yang aktif menjadi 'finished'
                RoomLease::where('room_id', $room->id)
                    ->where('status', 'active')
                    ->update(['status' => 'finished']);

                // Ubah status kamar menjadi 'tersedia'
                $room->update(['status' => 'tersedia']);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Penghuni berhasil checkout dan kamar '.$room->room_number.' kini tersedia.',
                'data' => $room->fresh(['roomType', 'activeLease.tenant']),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan checkout kamar: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
