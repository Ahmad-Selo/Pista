<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCreateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:stores,name'],
            'photo' => ['required', 'string', 'max:255'],
            'delivery_time' => ['required', 'date_format:H:i:s'],
            'user_id' => ['required', 'numeric', 'integer', 'min:1', 'exists:users,id'],
        ];
    }
}
