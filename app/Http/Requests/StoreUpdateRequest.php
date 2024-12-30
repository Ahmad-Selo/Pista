<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateRequest extends FormRequest
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
            'name' => ['string', 'max:255', 'unique:stores,name'],
            'photo' => ['string', 'max:255'],
            'delivery_time' => ['date_format:H:i:s'],
            'user_id' => ['numeric', 'integer', 'min:1', 'exists:users,id'],
        ];
    }
}
