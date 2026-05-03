<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->json('skills_required')->nullable();
            $table->string('location')->nullable();
            $table->string('job_type')->nullable();
            $table->string('salary_range')->nullable();
            $table->timestamps();

            $table->index('recruiter_id');
            $table->index('job_type');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
