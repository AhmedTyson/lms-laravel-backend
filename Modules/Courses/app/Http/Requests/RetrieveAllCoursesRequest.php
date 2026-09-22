<?php

namespace Modules\Courses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetrieveAllCoursesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1|max:100',
            'filter' => 'nullable|array',
            'filter.search' => 'nullable|string',
            'filter.category' => 'nullable|string',
            'filter.status' => 'nullable|string',
            'filter.published_at' => 'nullable|date',
            'filter.archived_at' => 'nullable|date',
            'filter.instructor_id' => 'nullable|integer',
            'sort' => 'nullable|string',
        ];
    }
}
