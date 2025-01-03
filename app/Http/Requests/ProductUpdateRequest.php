<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'name' => ['string', 'max:255', 'unique:products,name'],
            'description' => ['string', 'max:255'],
            'quantity' => ['numeric', 'integer', 'min:0'],
            'price' => ['numeric', 'gt:0'],
            'photo' => ['string', 'max:255'],
            'category' => ['string', 'max:255'],
        ];
    }
}
