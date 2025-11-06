<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'detail_address' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:20'],
            'working_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'price_per_kg' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048', 'dimensions:ratio=1/1'],
            'pickup_gojek' => ['nullable', 'boolean'],
            'pickup_grab' => ['nullable', 'boolean'],
            'pickup_free' => ['nullable', 'boolean'],
            'pickup_free_schedule' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Nama cabang maksimal 100 karakter',
            'detail_address.max' => 'Alamat lengkap maksimal 255 karakter',
            'latitude.numeric' => 'Latitude harus berupa angka',
            'latitude.between' => 'Latitude harus antara -90 dan 90',
            'longitude.numeric' => 'Longitude harus berupa angka',
            'longitude.between' => 'Longitude harus antara -180 dan 180',
            'phone.max' => 'Nomor telepon maksimal 20 karakter',
            'working_hours.integer' => 'Jam kerja harus berupa angka',
            'working_hours.min' => 'Jam kerja minimal 1 jam',
            'working_hours.max' => 'Jam kerja maksimal 24 jam',
            'price_per_kg.integer' => 'Harga per kg harus berupa angka',
            'price_per_kg.min' => 'Harga per kg minimal 0',
            'image.image' => 'File harus berupa gambar',
            'image.mimes' => 'Gambar harus berformat jpeg, jpg, png, atau webp',
            'image.max' => 'Ukuran gambar maksimal 2MB',
            'image.dimensions' => 'Gambar harus berbentuk kotak (ratio 1:1)',
            'pickup_gojek.boolean' => 'Pickup Gojek harus berupa boolean',
            'pickup_grab.boolean' => 'Pickup Grab harus berupa boolean',
            'pickup_free.boolean' => 'Pickup gratis harus berupa boolean',
            'pickup_free_schedule.array' => 'Jadwal pickup gratis harus berupa array',
        ];
    }
}
