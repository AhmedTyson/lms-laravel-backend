<?php

namespace Modules\AccessManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grantee_id' => ['required', 'integer', 'exists:users,id'],
            'permission_name' => ['required', 'string', 'max:255'],
            'group_id' => ['required', 'integer', 'exists:groups,id'],
        ];
    }
}
