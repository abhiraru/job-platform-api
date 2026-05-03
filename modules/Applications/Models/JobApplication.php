<?php

namespace Modules\Applications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Applications\Enums\ApplicationStatus;
use Modules\Jobs\Models\JobPost;

#[Fillable([
    'user_id',
    'job_id',
    'status',
    'resume_url',
    'cover_letter',
])]
class JobApplication extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPost::class, 'job_id');
    }
}
