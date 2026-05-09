<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quiz_records', function (Blueprint $table) {
            $table->id();
            // Now 'dasher' exists, so this won't fail
            $table->foreignId('user_id')->constrained('dasher')->onDelete('cascade');
            // IMPORTANT: Ensure the 'quizzes' migration file has a timestamp EARLIER than this file
            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            $table->integer('score')->default(0);
            $table->integer('total_questions')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_records');
    }
};
