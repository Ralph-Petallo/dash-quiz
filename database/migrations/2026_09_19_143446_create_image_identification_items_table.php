<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('image_identification_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_id')
                ->constrained('image_identification_questions')
                ->cascadeOnDelete();

            $table->string('item_name', 100);

            $table->string('image_path');

            $table->text('description');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'question_id',
                'sort_order'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_identification_items');
    }
};