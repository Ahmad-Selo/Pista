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
            'name_ar' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'description_ar' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'gt:0'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'category' => ['required', 'string', 'max:255', 'exists:categories,name'],
            'discount' => ['numeric', 'integer', 'between:0,99'],
            'started_at' => ['required_with:discount', 'date_format:Y-m-d H:i:s', 'after_or_equal:now'],
            'ended_at' => ['required_with:discount', 'date_format:Y-m-d H:i:s', 'after:started_at'],
            'quantity' => ['required', 'numeric', 'integer', 'min:0'],
        ];
    }
}
