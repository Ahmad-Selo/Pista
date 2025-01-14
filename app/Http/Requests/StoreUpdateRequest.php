<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'store_name' => ['string', 'max:255', Rule::unique('stores', 'name')->ignoreModel($this->store)],
            'image' => ['image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'user_id' => ['numeric', 'integer', 'min:1', 'exists:users,id'],
            'warehouse_name' => ['string', 'max:255', Rule::unique('warehouses', 'name')->ignoreModel($this->store->warehouse)],
            'address_name' => ['string', 'max:255'],
            'longitude' => ['required_with:address_name', 'numeric', 'between:-90,90'],
            'latitude' => ['required_with:address_name', 'numeric', 'between:-180,180'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = [];

        if ($this->has('store_name')) {
            $validated['store']['name'] = $this->store_name;
        }

        if ($this->has('user_id')) {
            $validated['store']['user_id'] = $this->user_id;
        }

        if ($this->has('warehouse_name')) {
            $validated['warehouse']['name'] = $this->warehouse_name;
        }

        if ($this->has('address_name')) {
            $validated['address'] = [
                'name' => $this->address_name,
                'longitude' => $this->longitude,
                'latitude' => $this->latitude,
            ];
        }

        return $validated;
    }
}
