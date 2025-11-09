<?php

namespace App\Domains\FollowUps\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FollowUpUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'due_date' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:pending,completed'],
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'],
        ];
    }
}
