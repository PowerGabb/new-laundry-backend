<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
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
            'order_status' => [
                'required',
                'string',
                Rule::in([
                    'pending',
                    'processing',
                    'washing',
                    'ready',
                    'picked_up',
                    'delivering',
                    'completed',
                    'cancelled',
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'order_status.required' => 'Status order harus diisi',
            'order_status.in' => 'Status order tidak valid',
            'notes.max' => 'Catatan maksimal 500 karakter',
        ];
    }
}
