<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quiz_records', function (Blueprint $table) {

            // Score details
            $table->unsignedInteger('correct_answers')
                ->nullable()
                ->after('score');

            $table->unsignedInteger('total_questions')
                ->nullable()
                ->after('correct_answers');

            $table->decimal('percentage', 5, 2)
                ->nullable()
                ->after('total_questions');

            // Passing / result information
            $table->decimal('passing_score', 5, 2)
                ->nullable()
                ->after('percentage');

            $table->string('status', 30)
                ->nullable()
                ->after('passing_score');

            // Number of times the quiz was attempted
            $table->unsignedInteger('attempt_count')
                ->default(1)
                ->after('status');

            // Optional quiz classification
            $table->string('quiz_type', 50)
                ->nullable()
                ->after('attempt_count');

            $table->string('category', 100)
                ->nullable()
                ->after('quiz_type');

            // When the quiz was completed
            $table->timestamp('completed_at')
                ->nullable()
                ->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_records', function (Blueprint $table) {
            $table->dropColumn([
                'correct_answers',
                'total_questions',
                'percentage',
                'passing_score',
                'status',
                'attempt_count',
                'quiz_type',
                'category',
                'completed_at',
            ]);
        });
    }
};