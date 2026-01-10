<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'code' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code de vérification est obligatoire.',
            'code.size' => 'Le code de vérification doit contenir exactement 6 chiffres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'code de vérification',
        ];
    }
}
