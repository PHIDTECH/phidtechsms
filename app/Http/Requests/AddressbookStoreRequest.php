<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressbookStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'addressbook' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
