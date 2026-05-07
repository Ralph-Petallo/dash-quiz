<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_record_id')
                ->constrained('quiz_records')
                ->onDelete('cascade');

            $table->foreignId('question_id')
                ->constrained('questions')
                ->onDelete('cascade');

            $table->foreignId('selected_option_id')
                ->nullable()
                ->constrained('question_options')
                ->onDelete('set null');

            $table->boolean('is_correct')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
