<?php

namespace App\Domains\Clients\Requests;

use App\Domains\Clients\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($this->route('client'))],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in([Client::STATUS_HOT, Client::STATUS_WARM, Client::STATUS_INACTIVE])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
