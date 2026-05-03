<?php

namespace Modules\Profile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Profile\Enums\ProfileAvailability;

#[Fillable([
    'headline',
    'current_title',
    'desired_job_title',
    'bio',
    'phone',
    'location',
    'years_of_experience',
    'skills',
    'linkedin_url',
    'portfolio_url',
    'resume_url',
    'availability_status',
])]
class Profile extends Model
{
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'availability_status' => ProfileAvailability::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
