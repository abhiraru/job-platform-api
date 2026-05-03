<?php

namespace Modules\AI\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Jobs\Models\JobPost;

#[Fillable([
    'user_id',
    'job_id',
    'match_score',
    'missing_skills',
    'summary',
    'user_skills',
    'job_skills',
    'source',
])]
class AIMatch extends Model
{
    protected $table = 'ai_matches';

    protected function casts(): array
    {
        return [
            'missing_skills' => 'array',
            'user_skills' => 'array',
            'job_skills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPost::class, 'job_id');
    }
}
