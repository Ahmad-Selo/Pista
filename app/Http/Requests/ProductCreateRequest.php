<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProductCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        return ($this->store->user->id == $user->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:products,name'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'gt:0'],
            'photo' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
        ];
    }
}
