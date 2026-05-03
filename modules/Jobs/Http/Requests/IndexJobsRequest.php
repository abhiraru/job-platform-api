<?php

namespace Modules\Jobs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Jobs\Enums\JobType;

class IndexJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Validation\Rules\Enum>>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.job_type' => ['sometimes', 'nullable', Rule::enum(JobType::class)],
            'filter.skill' => ['sometimes', 'nullable', 'string', 'max:60'],
            'filter.recruiter_id' => ['sometimes', 'integer', 'exists:users,id'],
            'page' => ['sometimes', 'array'],
            'page.number' => ['sometimes', 'integer', 'min:1'],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in([
                'created_at',
                '-created_at',
                'title',
                '-title',
                'location',
                '-location',
                'job_type',
                '-job_type',
                'salary_range',
                '-salary_range',
            ])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => $validated['filter']['search'] ?? null,
            'location' => $validated['filter']['location'] ?? null,
            'job_type' => $validated['filter']['job_type'] ?? null,
            'skill' => $validated['filter']['skill'] ?? null,
            'recruiter_id' => $validated['filter']['recruiter_id'] ?? null,
            'page' => $validated['page']['number'] ?? 1,
            'per_page' => $validated['page']['size'] ?? 15,
            'sort' => $validated['sort'] ?? '-created_at',
        ];
    }
}
