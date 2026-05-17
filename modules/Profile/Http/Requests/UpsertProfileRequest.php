<?php

namespace Modules\Profile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Modules\Profile\Enums\ProfileAvailability;

class UpsertProfileRequest extends FormRequest
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
            'data' => ['required', 'array'],
            'data.type' => ['required', 'string', Rule::in(['profiles'])],
            'data.id' => ['sometimes', 'nullable', 'string'],
            'data.attributes' => ['required', 'array'],
            'data.attributes.headline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data.attributes.current_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data.attributes.desired_job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data.attributes.bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'data.attributes.phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'data.attributes.location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data.attributes.years_of_experience' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:60'],
            'data.attributes.skills' => ['sometimes', 'nullable', 'array', 'max:50'],
            'data.attributes.skills.*' => ['string', 'max:60'],
            'data.attributes.linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'data.attributes.portfolio_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'data.attributes.availability_status' => ['sometimes', 'nullable', Rule::enum(ProfileAvailability::class)],
            'resume' => ['sometimes', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'profile_picture' => ['sometimes', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        return $this->validated('data.attributes', []);
    }

    public function resumeFile(): ?UploadedFile
    {
        $resume = $this->file('resume');

        return $resume instanceof UploadedFile ? $resume : null;
    }

    public function profilePictureFile(): ?UploadedFile
    {
        $picture = $this->file('profile_picture');

        return $picture instanceof UploadedFile ? $picture : null;
    }
}
