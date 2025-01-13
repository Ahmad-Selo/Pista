<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as RulesPassword;

class UserCreateRequest extends FormRequest
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
            'phone'=>['required','digits:10', 'regex:/^[0-9]+$/','unique:users,phone'],
            'password'=>['required','confirmed','max:30','min:8','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
            'first_name'=>['required','max:20','min:3'],
            'last_name'=>['required','max:20','min:2'],
            'photo'=>['sometimes|image'],
            'latitude'=>['required','numeric'],
            'longitude'=>['required','numeric'],
            'name'=>['required','string','max:255'],
            'code'=>['required']
        ];
    }

    public function validated($key = null, $default = null)
    {
        return [
            'phone'=>$this->phone,
            'password'=>$this->password,
            'first_name'=>$this->first_name,
            'last_name'=>$this->last_name,
            'photo'=>$this->photo,
            'address' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'name' => $this->name,
            ],
        ];
    }
}
