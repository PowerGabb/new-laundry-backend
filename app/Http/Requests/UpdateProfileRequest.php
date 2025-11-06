<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
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
            'name.max' => 'Nama maksimal 255 karakter',
            'phone.max' => 'Nomor telepon maksimal 20 karakter',
            'profile_image.image' => 'File harus berupa gambar',
            'profile_image.mimes' => 'Gambar harus berformat jpeg, jpg, png, atau webp',
            'profile_image.max' => 'Ukuran gambar maksimal 2MB',
        ];
    }
}
