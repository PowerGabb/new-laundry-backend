<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'branch_id' => 'required|exists:branches,id',
            'estimated_weight' => 'required|integer|min:1',
            'customer_address' => 'required|string',
            'customer_latitude' => 'required|numeric',
            'customer_longitude' => 'required|numeric',
            'customer_phone' => 'required|string|max:20',
            'customer_name' => 'nullable|string|max:100',
            'pickup_scheduled_time' => 'nullable|string',
            'notes' => 'nullable|string',
            'special_instructions' => 'nullable|string',

            // Pickup method (free atau courier)
            'pickup_method' => 'required|in:free_pickup,gojek,grab',

            // Courier info - required jika pickup_method adalah gojek atau grab
            'company' => 'required_unless:pickup_method,free_pickup|string',
            'courier_name' => 'required_unless:pickup_method,free_pickup|string',
            'courier_code' => 'required_unless:pickup_method,free_pickup|string',
            'courier_service_name' => 'required_unless:pickup_method,free_pickup|string',
            'courier_service_code' => 'required_unless:pickup_method,free_pickup|string',
            'currency' => 'nullable|string',
            'description' => 'nullable|string',
            'duration' => 'nullable|string',
            'shipment_duration_range' => 'nullable|string',
            'shipment_duration_unit' => 'nullable|string',
            'service_type' => 'nullable|string',
            'shipping_type' => 'nullable|string',
            'price' => 'required_unless:pickup_method,free_pickup|integer|min:0',
            'shipping_fee' => 'required_unless:pickup_method,free_pickup|integer|min:0',
            'shipping_fee_discount' => 'nullable|integer|min:0',
            'shipping_fee_surcharge' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch harus dipilih',
            'branch_id.exists' => 'Branch tidak ditemukan',
            'estimated_weight.required' => 'Estimasi berat cucian harus diisi',
            'estimated_weight.min' => 'Estimasi berat minimal 1 kg',
            'customer_address.required' => 'Alamat customer harus diisi',
            'customer_latitude.required' => 'Lokasi customer harus diisi',
            'customer_longitude.required' => 'Lokasi customer harus diisi',
            'customer_phone.required' => 'Nomor telepon customer harus diisi',
            'pickup_method.required' => 'Metode pickup harus dipilih',
            'pickup_method.in' => 'Metode pickup tidak valid',
            'company.required_unless' => 'Informasi kurir harus diisi',
            'courier_name.required_unless' => 'Nama kurir harus diisi',
            'courier_code.required_unless' => 'Kode kurir harus diisi',
            'courier_service_name.required_unless' => 'Nama layanan kurir harus diisi',
            'courier_service_code.required_unless' => 'Kode layanan kurir harus diisi',
            'price.required_unless' => 'Harga kurir harus diisi',
            'shipping_fee.required_unless' => 'Biaya pengiriman harus diisi',
        ];
    }
}
