<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class StorePackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'max_doors' => ['required', 'integer'],
            'door_label' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'billing_cycle' => ['nullable', 'string', 'max:50'],
            'badge_text' => ['nullable', 'string', 'max:255'],
            'discount_label' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
            'description' => ['required', 'string'],
            'features' => ['required', 'array'],
            'features.*' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama paket wajib diisi.',
            'category.required' => 'Kategori paket wajib diisi.',
            'max_doors.required' => 'Batas jumlah pintu wajib diisi.',
            'door_label.required' => 'Label pintu wajib diisi.',
            'price.required' => 'Harga paket wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'duration_days.required' => 'Durasi hari wajib diisi.',
            'duration_days.integer' => 'Durasi hari harus berupa bilangan bulat.',
            'description.required' => 'Deskripsi paket wajib diisi.',
            'features.required' => 'Daftar fitur paket wajib diisi.',
            'features.array' => 'Daftar fitur harus berupa array.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validasi gagal.',
            'errors' => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
