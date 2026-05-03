<?php

namespace Modules\Applications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Applications\Enums\ApplicationStatus;
use Modules\Jobs\Enums\JobType;

class IndexApplicationsRequest extends FormRequest
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
            'filter.status' => ['sometimes', 'nullable', Rule::enum(ApplicationStatus::class)],
            'filter.job_type' => ['sometimes', 'nullable', Rule::enum(JobType::class)],
            'filter.search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.candidate' => ['sometimes', 'nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'array'],
            'page.number' => ['sometimes', 'integer', 'min:1'],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in(['created_at', '-created_at', 'updated_at', '-updated_at', 'status', '-status'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'status' => $validated['filter']['status'] ?? null,
            'job_type' => $validated['filter']['job_type'] ?? null,
            'search' => $validated['filter']['search'] ?? null,
            'candidate' => $validated['filter']['candidate'] ?? null,
            'page' => $validated['page']['number'] ?? 1,
            'per_page' => $validated['page']['size'] ?? 15,
            'sort' => $validated['sort'] ?? '-created_at',
        ];
    }
}
