<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourierRateRequest extends FormRequest
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
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'destination_latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'type' => ['required', 'string', 'in:pickup,delivery'],
            'weight' => ['nullable', 'integer', 'min:100'],
            'value' => ['nullable', 'integer', 'min:0'],
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
            'branch_id.required' => 'ID cabang wajib diisi',
            'branch_id.exists' => 'Cabang tidak ditemukan',
            'destination_latitude.required' => 'Latitude tujuan wajib diisi',
            'destination_latitude.numeric' => 'Latitude tujuan harus berupa angka',
            'destination_latitude.between' => 'Latitude tujuan harus antara -90 dan 90',
            'destination_longitude.required' => 'Longitude tujuan wajib diisi',
            'destination_longitude.numeric' => 'Longitude tujuan harus berupa angka',
            'destination_longitude.between' => 'Longitude tujuan harus antara -180 dan 180',
            'type.required' => 'Tipe pengiriman wajib diisi',
            'type.in' => 'Tipe pengiriman harus pickup atau delivery',
            'weight.integer' => 'Berat harus berupa angka',
            'weight.min' => 'Berat minimal 100 gram',
            'value.integer' => 'Nilai barang harus berupa angka',
            'value.min' => 'Nilai barang minimal 0',
        ];
    }
}
