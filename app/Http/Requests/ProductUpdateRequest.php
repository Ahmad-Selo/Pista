<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        return ($this->product->store->user->id == $user->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255', Rule::unique('products', 'name')->ignoreModel($this->product)],
            'name_ar' => ['string', 'max:255'],
            'description' => ['string', 'max:255'],
            'description_ar' => ['string', 'max:255'],
            'price' => ['numeric', 'gt:0'],
            'image' => ['image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'category' => ['string', 'max:255', 'exists:categories,name'],
            'discount' => ['numeric', 'integer', 'between:0,99'],
            'started_at' => ['required_with:discount', 'date_format:Y-m-d H:i:s', 'after_or_equal:now'],
            'ended_at' => ['required_with:discount', 'date_format:Y-m-d H:i:s', 'after:started_at'],
            'quantity' => ['numeric', 'integer', 'min:0'],
        ];
    }
}
