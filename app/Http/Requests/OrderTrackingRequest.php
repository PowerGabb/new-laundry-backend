<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderTrackingRequest extends FormRequest
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
            'waybill_id' => ['required_without:courier_waybill_id', 'string'],
            'courier_waybill_id' => ['required_without:waybill_id', 'string'],
            'courier' => ['required_with:courier_waybill_id', 'string', 'in:gojek,grab'],
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
            'waybill_id.required_without' => 'Waybill ID atau Courier Waybill ID wajib diisi',
            'waybill_id.string' => 'Waybill ID harus berupa string',
            'courier_waybill_id.required_without' => 'Courier Waybill ID atau Waybill ID wajib diisi',
            'courier_waybill_id.string' => 'Courier Waybill ID harus berupa string',
            'courier.required_with' => 'Courier wajib diisi jika menggunakan Courier Waybill ID',
            'courier.in' => 'Courier harus gojek atau grab',
        ];
    }
}
