<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('image_identification_items', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->dropColumn('question_id');
        });
    }

    public function down(): void
    {
        Schema::table('image_identification_items', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->nullable()->after('assessment_id');
            $table->foreign('question_id')->references('id')->on('questions')->nullOnDelete();
        });
    }
};