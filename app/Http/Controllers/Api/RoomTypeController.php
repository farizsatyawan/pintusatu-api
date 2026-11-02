<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoomTypeController extends Controller
{
    /**
     * Menampilkan daftar semua tipe kamar beserta fasilitasnya.
     */
    public function index(): JsonResponse
    {
        $roomTypes = RoomType::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar tipe kamar berhasil diambil.',
            'data' => $roomTypes,
        ], Response::HTTP_OK);
    }

    /**
     * Membuat tipe kamar baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'facilities' => 'required|array',
            'facilities.*' => 'string|max:100',
        ]);

        $roomType = RoomType::create([
            'name' => $validated['name'],
            'facilities' => $validated['facilities'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tipe kamar berhasil dibuat.',
            'data' => $roomType,
        ], Response::HTTP_CREATED);
    }

    /**
     * Menampilkan detail 1 tipe kamar.
     */
    public function show(Request $request, $id = null): JsonResponse
    {
        $typeId = $request->query('id') ?: $id;
        $roomType = RoomType::find($typeId);

        if (! $roomType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipe kamar tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail tipe kamar berhasil diambil.',
            'data' => $roomType,
        ], Response::HTTP_OK);
    }

    /**
     * Memperbarui data tipe kamar.
     */
    public function update(Request $request, $id = null): JsonResponse
    {
        $typeId = $request->query('id') ?: $id;
        $roomType = RoomType::find($typeId);

        if (! $roomType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipe kamar tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'facilities' => 'sometimes|required|array',
            'facilities.*' => 'string|max:100',
        ]);

        $roomType->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Tipe kamar berhasil diperbarui.',
            'data' => $roomType->fresh(),
        ], Response::HTTP_OK);
    }

    /**
     * Menghapus tipe kamar.
     */
    public function destroy(Request $request, $id = null): JsonResponse
    {
        $typeId = $request->query('id') ?: $id;
        $roomType = RoomType::find($typeId);

        if (! $roomType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipe kamar tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $roomType->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tipe kamar berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
