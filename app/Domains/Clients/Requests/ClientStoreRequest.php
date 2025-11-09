<?php

namespace App\Domains\Clients\Requests;

use App\Domains\Clients\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:clients,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in([Client::STATUS_HOT, Client::STATUS_WARM, Client::STATUS_INACTIVE])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
