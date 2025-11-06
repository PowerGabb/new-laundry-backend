<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActualWeightRequest extends FormRequest
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
            // Actual weight items (required)
            'actual_weight_items' => 'required|array|min:1',
            'actual_weight_items.*.item_id' => 'required|integer|exists:laundry_items,id',
            'actual_weight_items.*.item_name' => 'required|string',
            'actual_weight_items.*.quantity' => 'required|numeric|min:0.1',
            'actual_weight_items.*.unit' => 'required|in:kg,pcs',
            'actual_weight_items.*.price_per_unit' => 'required|integer|min:0',
            'actual_weight_items.*.subtotal' => 'required|integer|min:0',

            // Optional: actual weight in kg (for legacy or summary)
            'actual_weight' => 'nullable|numeric|min:0',

            // Video proof URL (optional but recommended)
            'proof_video_url' => 'nullable|url',

            // Optional notes about the actual weight
            'notes' => 'nullable|string',
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
            'actual_weight_items.required' => 'Item actual harus diisi',
            'actual_weight_items.min' => 'Minimal 1 item actual harus diisi',
            'actual_weight_items.*.item_id.required' => 'Item ID harus diisi',
            'actual_weight_items.*.item_id.exists' => 'Item tidak ditemukan',
            'actual_weight_items.*.item_name.required' => 'Nama item harus diisi',
            'actual_weight_items.*.quantity.required' => 'Jumlah actual harus diisi',
            'actual_weight_items.*.quantity.min' => 'Jumlah actual minimal 0.1',
            'actual_weight_items.*.unit.required' => 'Satuan item harus diisi',
            'actual_weight_items.*.unit.in' => 'Satuan item harus kg atau pcs',
            'actual_weight_items.*.price_per_unit.required' => 'Harga per unit harus diisi',
            'actual_weight_items.*.subtotal.required' => 'Subtotal harus diisi',
            'proof_video_url.url' => 'Format URL video tidak valid',
        ];
    }
}
