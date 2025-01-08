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
            'store_name' => ['required', 'string', 'max:255', 'unique:stores,name'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'user_id' => ['required', 'numeric', 'integer', 'min:1', 'exists:users,id'],
            'warehouse_name' => ['required', 'string', 'max:255', 'unique:warehouses,name'],
            'address_name' => ['required', 'string', 'max:255'],
            'longitude' => ['required', 'numeric', 'between:-90,90'],
            'latitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        return [
            'store' => [
                'name' => $this->store_name,
                'user_id' => $this->user_id,
            ],
            'warehouse' => [
                'name' => $this->warehouse_name,
            ],
            'address' => [
                'name' => $this->address_name,
                'longitude' => $this->longitude,
                'latitude' => $this->latitude,
            ]
        ];
    }
}
