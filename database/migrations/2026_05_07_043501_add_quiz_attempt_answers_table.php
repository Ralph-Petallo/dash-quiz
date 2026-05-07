<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();

            // each attempt belongs to a quiz record
            $table->foreignId('quiz_record_id')
                ->constrained('quiz_records')
                ->onDelete('cascade');

            // question being answered
            $table->foreignId('question_id')
                ->constrained('questions')
                ->onDelete('cascade');

            // selected option by user
            $table->foreignId('selected_option_id')
                ->nullable()
                ->constrained('question_options')
                ->onDelete('set null');

            // snapshot of correctness at time of answering
            $table->boolean('is_correct')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
    }
};
