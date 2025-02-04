<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckPasswordRequest extends FormRequest
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
            'password'=>['required','max:30','min:8','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
            'newPassword'=>['required','max:30','min:8','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/']
        ];
    }
}
