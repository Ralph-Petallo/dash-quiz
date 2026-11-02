<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('input_output_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('dasher')
                ->nullOnDelete();

            $table->foreignId('quiz_record_id')
                ->nullable()
                ->constrained('quiz_records')
                ->nullOnDelete();

            $table->uuid('session_uuid');

            $table->foreignId('device_id')
                ->constrained('input_output_devices')
                ->cascadeOnDelete();

            $table->enum('selected_type', ['input', 'output']);
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('points')->default(0);
            $table->timestamps();

            $table->unique(
                ['user_id', 'session_uuid', 'device_id'],
                'io_attempt_user_session_device_unique'
            );

            $table->index(['user_id', 'created_at']);
            $table->index(['session_uuid', 'device_id']);
            $table->index('quiz_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_output_attempts');
    }
};