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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('difficulty')->nullable()->after('description');
            $table->string('coc_number')->nullable()->after('difficulty');
            $table->string('category')->nullable()->after('difficulty');
            $table->string('quiz_type')->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['quiz_type', 'difficulty', 'category', 'coc_number']);
        });
    }
};
