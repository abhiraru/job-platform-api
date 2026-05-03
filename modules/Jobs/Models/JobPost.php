<?php

namespace Modules\Jobs\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AI\Models\AIMatch;
use Modules\Applications\Models\JobApplication;
use Modules\Jobs\Enums\JobType;

#[Fillable([
    'recruiter_id',
    'title',
    'description',
    'skills_required',
    'location',
    'job_type',
    'salary_range',
])]
class JobPost extends Model
{
    protected function casts(): array
    {
        return [
            'skills_required' => 'array',
            'job_type' => JobType::class,
        ];
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }

    public function aiMatches(): HasMany
    {
        return $this->hasMany(AIMatch::class, 'job_id');
    }
}
