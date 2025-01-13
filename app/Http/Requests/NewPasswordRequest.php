<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewPasswordRequest extends FormRequest
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
            'phone'=>['required','digits:10', 'regex:/^[0-9]+$/','starts_with:09'],
            'code'=>['required','digits:4','regex:/^[0-9]+$/'],
            'newPassword'=>['required','max:30','min:8','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/']
        ];
    }
}
