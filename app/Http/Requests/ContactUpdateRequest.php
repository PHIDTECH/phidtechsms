<?php

namespace App\Http\Requests;

use App\Rules\E164Phone;
use Illuminate\Foundation\Http\FormRequest;

class ContactUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fname' => ['nullable', 'string', 'max:60'],
            'lname' => ['nullable', 'string', 'max:60'],
            'title' => ['nullable', 'string', 'max:10'],
            'mob_no' => ['required', 'string', new E164Phone()],
            'mob_no2' => ['nullable', 'string', new E164Phone()],
            'email' => ['nullable', 'email'],
            'gender' => ['nullable', 'in:male,female'],
            'country' => ['nullable', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:60'],
            'area' => ['nullable', 'string', 'max:60'],
            'birth_date' => ['nullable', 'date'],
            'addressbook_id' => ['required', 'array', 'min:1'],
            'addressbook_id.*' => ['string'],
        ];
    }
}
