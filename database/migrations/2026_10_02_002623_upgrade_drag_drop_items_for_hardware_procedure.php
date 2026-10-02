<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Use this migration ONLY when your original drag_drop_items table already exists.
        if (!Schema::hasColumn('drag_drop_items', 'component_key')) {
            Schema::table('drag_drop_items', function (Blueprint $table) {
                $table->string('component_key', 50)
                    ->nullable()
                    ->after('question_id');
            });
        }

        if (!Schema::hasColumn('drag_drop_items', 'instruction')) {
            Schema::table('drag_drop_items', function (Blueprint $table) {
                $table->text('instruction')
                    ->nullable()
                    ->after('item_text');
            });
        }

        if (!Schema::hasColumn('drag_drop_items', 'phase')) {
            Schema::table('drag_drop_items', function (Blueprint $table) {
                $table->string('phase', 20)
                    ->default('disassemble')
                    ->after('item_image_path');
            });
        }

        if (!Schema::hasColumn('drag_drop_items', 'action')) {
            Schema::table('drag_drop_items', function (Blueprint $table) {
                $table->string('action', 20)
                    ->default('remove')
                    ->after('phase');
            });
        }

        if (!Schema::hasColumn('drag_drop_items', 'required')) {
            Schema::table('drag_drop_items', function (Blueprint $table) {
                $table->boolean('required')
                    ->default(true)
                    ->after('correct_position');
            });
        }

        if (!Schema::hasColumn('drag_drop_items', 'active')) {
            Schema::table('drag_drop_items', function (Blueprint $table) {
                $table->boolean('active')
                    ->default(true)
                    ->after('required');
            });
        }

        if (!Schema::hasTable('drag_drop_attempts')) {
            Schema::create('drag_drop_attempts', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();

                $table->foreignId('quiz_id')
                    ->constrained('quizzes')
                    ->cascadeOnDelete();

                $table->foreignId('question_id')
                    ->constrained('questions')
                    ->cascadeOnDelete();

                $table->foreignId('quiz_record_id')
                    ->nullable()
                    ->constrained('quiz_records')
                    ->nullOnDelete();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('dasher')
                    ->nullOnDelete();

                $table->unsignedSmallInteger('completed_steps')->default(0);
                $table->unsignedSmallInteger('correct_steps')->default(0);
                $table->unsignedSmallInteger('total_steps')->default(0);
                $table->unsignedSmallInteger('mistake_count')->default(0);
                $table->decimal('percentage', 5, 2)->default(0);
                $table->boolean('passed')->default(false);
                $table->unsignedInteger('elapsed_time')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'quiz_id']);
            });
        }

        if (!Schema::hasTable('drag_drop_attempt_steps')) {
            Schema::create('drag_drop_attempt_steps', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();

                $table->foreignId('drag_drop_attempt_id')
                    ->constrained('drag_drop_attempts')
                    ->cascadeOnDelete();

                $table->foreignId('expected_item_id')
                    ->nullable()
                    ->constrained('drag_drop_items')
                    ->nullOnDelete();

                $table->unsignedInteger('sequence_number');
                $table->unsignedInteger('expected_position')->nullable();
                $table->string('phase', 20)->nullable();
                $table->string('expected_action', 20)->nullable();
                $table->string('expected_component_key', 50)->nullable();
                $table->string('performed_component_key', 50);
                $table->string('destination_zone', 20);
                $table->boolean('is_correct')->default(false);
                $table->boolean('first_try')->default(false);
                $table->timestamps();

                // Explicit short name avoids MySQL's 64-character identifier limit.
                $table->index(
                    ['drag_drop_attempt_id', 'sequence_number'],
                    'dd_attempt_steps_attempt_seq_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('drag_drop_attempt_steps');
        Schema::dropIfExists('drag_drop_attempts');

        // Keep the original drag_drop_items table, removing only columns added here.
        $columns = [
            'component_key',
            'instruction',
            'phase',
            'action',
            'required',
            'active',
        ];

        $existing = array_values(array_filter(
            $columns,
            fn($column) => Schema::hasColumn('drag_drop_items', $column)
        ));

        if ($existing) {
            Schema::table('drag_drop_items', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};