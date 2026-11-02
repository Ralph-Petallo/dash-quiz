<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('image_identification_items', function (Blueprint $table) {
            $table->renameColumn('quiz_id', 'assessment_id');
        });
    }

    public function down(): void
    {
        Schema::table('image_identification_items', function (Blueprint $table) {
            $table->renameColumn('assessment_id', 'quiz_id');
        });
    }
};
