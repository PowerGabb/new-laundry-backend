<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NearbyBranchRequest extends FormRequest
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:100', 'max:50000'],
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
            'latitude.required' => 'Latitude wajib diisi',
            'latitude.numeric' => 'Latitude harus berupa angka',
            'latitude.between' => 'Latitude harus antara -90 dan 90',
            'longitude.required' => 'Longitude wajib diisi',
            'longitude.numeric' => 'Longitude harus berupa angka',
            'longitude.between' => 'Longitude harus antara -180 dan 180',
            'radius.integer' => 'Radius harus berupa angka',
            'radius.min' => 'Radius minimal 100 meter',
            'radius.max' => 'Radius maksimal 50000 meter',
        ];
    }

    /**
     * Get the validated data with default radius.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['radius'] = $data['radius'] ?? 5000; // Default 5km

        return $data;
    }
}
