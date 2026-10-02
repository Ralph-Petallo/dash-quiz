<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rj45_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->unique()->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('assessment_id')->unique()->constrained('assessments')->cascadeOnDelete();
            $table->string('app_name')->default('CSS Prep');
            $table->string('project_title');
            $table->string('project_subtitle')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('coc_label')->nullable();
            $table->char('default_standard', 1)->default('B');
            $table->unsignedSmallInteger('test_pin_delay_ms')->default(0);
            $table->json('tray_order')->nullable();
            $table->json('initial_wiring')->nullable();
            $table->text('simulation_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rj45_pinouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rj45_assessment_id')->constrained('rj45_assessments')->cascadeOnDelete();
            $table->char('standard', 1);
            $table->unsignedTinyInteger('pin_number');
            $table->string('wire_code', 10);
            $table->string('wire_name', 50);
            $table->string('short_label', 10);
            $table->string('color_hex', 7);
            $table->boolean('striped')->default(false);
            $table->timestamps();

            $table->unique(['rj45_assessment_id', 'standard', 'pin_number'], 'rj45_pinout_position_unique');
        });

        Schema::create('rj45_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rj45_assessment_id')->constrained('rj45_assessments')->cascadeOnDelete();
            $table->foreignId('quiz_record_id')->constrained('quiz_records')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('dasher')->nullOnDelete();
            $table->char('standard', 1);
            $table->json('wiring');
            $table->unsignedTinyInteger('correct_pins')->default(0);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->boolean('passed')->default(false);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'quiz_id', 'rj45_assessment_id']);
        });

        Schema::create('rj45_attempt_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rj45_attempt_id')->constrained('rj45_attempts')->cascadeOnDelete();
            $table->unsignedTinyInteger('pin_number');
            $table->string('actual_wire', 10)->nullable();
            $table->string('expected_wire', 10);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['rj45_attempt_id', 'pin_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rj45_attempt_pins');
        Schema::dropIfExists('rj45_attempts');
        Schema::dropIfExists('rj45_pinouts');
        Schema::dropIfExists('rj45_assessments');
    }
};
