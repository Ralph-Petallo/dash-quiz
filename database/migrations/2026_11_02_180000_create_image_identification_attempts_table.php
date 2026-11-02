<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_identification_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('dasher')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->uuid('token')->unique();
            $table->json('question_set');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'assessment_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_identification_attempts');
    }
};
