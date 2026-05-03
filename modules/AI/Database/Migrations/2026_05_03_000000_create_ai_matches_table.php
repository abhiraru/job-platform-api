<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('job_posts')->cascadeOnDelete();
            $table->unsignedTinyInteger('match_score');
            $table->json('missing_skills');
            $table->text('summary');
            $table->json('user_skills');
            $table->json('job_skills');
            $table->string('source')->default('fallback');
            $table->timestamps();

            $table->unique(['user_id', 'job_id']);
            $table->index('match_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_matches');
    }
};
