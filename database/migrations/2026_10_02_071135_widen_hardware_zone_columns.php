<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('drag_drop_items', function (Blueprint $table) {
            $table->string('target_zone', 50)
                ->nullable()
                ->change();
        });

        Schema::table('drag_drop_attempt_steps', function (Blueprint $table) {
            $table->string('source_zone', 50)
                ->nullable()
                ->change();

            $table->string('destination_zone', 50)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('drag_drop_items', function (Blueprint $table) {
            $table->string('target_zone', 20)
                ->nullable()
                ->change();
        });

        Schema::table('drag_drop_attempt_steps', function (Blueprint $table) {
            $table->string('source_zone', 20)
                ->nullable()
                ->change();

            $table->string('destination_zone', 20)
                ->change();
        });
    }
};