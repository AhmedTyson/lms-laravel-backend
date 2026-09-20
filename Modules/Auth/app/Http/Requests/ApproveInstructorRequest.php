<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve-instructors') ?? false;
    }

    public function rules(): array
    {
        return [
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
