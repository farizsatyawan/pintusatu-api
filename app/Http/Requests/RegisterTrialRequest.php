<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class RegisterTrialRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'nama_kos' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:50'],
            'jumlah_pintu' => ['required', 'integer', 'min:1'],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
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
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal harus 6 karakter.',
            'nama_kos.required' => 'Nama kos wajib diisi.',
            'nama_kos.string' => 'Nama kos harus berupa teks.',
            'nama_kos.max' => 'Nama kos maksimal 255 karakter.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.string' => 'Nomor WhatsApp harus berupa teks.',
            'whatsapp.max' => 'Nomor WhatsApp maksimal 50 karakter.',
            'jumlah_pintu.required' => 'Jumlah pintu wajib diisi.',
            'jumlah_pintu.integer' => 'Jumlah pintu harus berupa bilangan bulat.',
            'jumlah_pintu.min' => 'Jumlah pintu minimal 1 pintu.',
            'package_id.required' => 'Paket trial wajib dipilih.',
            'package_id.integer' => 'ID Paket harus berupa angka.',
            'package_id.exists' => 'Paket langganan yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * Validasi kapasitas maksimal pintu paket yang dipilih (max_doors).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $packageId = $this->input('package_id');
            $jumlahPintu = (int) $this->input('jumlah_pintu');

            if ($packageId && $jumlahPintu) {
                $package = \App\Models\Package::find($packageId);
                if ($package && $package->max_doors !== -1 && $jumlahPintu > $package->max_doors) {
                    $validator->errors()->add(
                        'jumlah_pintu',
                        "Jumlah pintu ({$jumlahPintu}) melebihi kapasitas maksimal paket yang dipilih (Maks. {$package->max_doors} pintu)."
                    );
                }
            }
        });
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
