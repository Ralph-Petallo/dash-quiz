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
        Schema::table('quiz_records', function (Blueprint $table) {
            $table->integer('elapsed_time')->nullable(); // seconds
        });
    }

    public function down(): void
    {
        Schema::table('quiz_records', function (Blueprint $table) {
            $table->dropColumn('elapsed_time');
        });
    }
};
