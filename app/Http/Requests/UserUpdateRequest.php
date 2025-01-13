<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
            'first_name'=>['sometimes','max:30','min:3'],
            'last_name'=>['sometimes','max:30','min:2'],
            'photo'=>['sometimes','image'],
            'latitude'=>['sometimes','numeric'],
            'longitude'=>['sometimes','numeric'],
            'name'=>['sometimes','string','max:255'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = [];

        if ($this->has('first_name')) {
            $validated['user_info']['first_name'] = $this->first_name;
        }

        if ($this->has('last_name')) {
            $validated['user_info']['last_name'] = $this->last_name;
        }


        if ($this->has('name')) {
            $validated['address'] = [
                'name' => $this->name,
                'longitude' => $this->longitude,
                'latitude' => $this->latitude,
            ];
        }

        return $validated;
    }
}
