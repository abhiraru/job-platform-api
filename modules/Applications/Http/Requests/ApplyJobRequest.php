<?php

namespace Modules\Applications\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ApplyJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Candidate;
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException($this->forbidden('Only candidates can apply to jobs.'));
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'job_id' => [
                'required',
                'integer',
                'exists:job_posts,id',
                Rule::unique('job_applications', 'job_id')->where('user_id', $this->user()?->id),
            ],
            'resume_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'cover_letter' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationAttributes(): array
    {
        return $this->safe()->only(['resume_url', 'cover_letter']);
    }

    private function forbidden(string $detail)
    {
        return response()
            ->json([
                'jsonapi' => [
                    'version' => '1.1',
                ],
                'errors' => [
                    [
                        'status' => '403',
                        'title' => 'Forbidden',
                        'detail' => $detail,
                    ],
                ],
            ], 403)
            ->header('Content-Type', 'application/vnd.api+json');
    }
}
