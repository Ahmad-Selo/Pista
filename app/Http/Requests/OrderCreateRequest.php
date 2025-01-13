<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderCreateRequest extends FormRequest
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
            'latitude'=>['required','numeric'],
            'longitude'=>['required','numeric'],
            'name'=>['required','string','max:255']

        ];
    }
    public function validated($key = null, $default = null)
    {
        return [
            'address' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'name' => $this->name,
            ],
        ];
    }
}
