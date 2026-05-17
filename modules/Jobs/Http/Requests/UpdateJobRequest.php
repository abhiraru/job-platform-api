<?php

namespace Modules\Jobs\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Modules\Jobs\Enums\JobType;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Employer;
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()
                ->json([
                    'jsonapi' => [
                        'version' => '1.1',
                    ],
                    'errors' => [
                        [
                            'status' => '403',
                            'title' => 'Forbidden',
                            'detail' => 'Only recruiters can update jobs.',
                        ],
                    ],
                ], 403)
                ->header('Content-Type', 'application/vnd.api+json'),
        );
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Validation\Rules\Enum>>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.type' => ['required', 'string', Rule::in(['jobs'])],
            'data.attributes' => ['required', 'array'],
            'data.attributes.title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data.attributes.description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'data.attributes.skills_required' => ['sometimes', 'nullable', 'array', 'max:50'],
            'data.attributes.skills_required.*' => ['string', 'max:60'],
            'data.attributes.location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data.attributes.job_type' => ['sometimes', 'nullable', Rule::enum(JobType::class)],
            'data.attributes.salary_range' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jobAttributes(): array
    {
        return $this->validated('data.attributes', []);
    }
}
