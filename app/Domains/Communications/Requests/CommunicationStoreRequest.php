<?php

namespace App\Domains\Communications\Requests;

use App\Domains\Communications\Models\Communication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommunicationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([Communication::TYPE_CALL, Communication::TYPE_EMAIL, Communication::TYPE_MEETING])],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
