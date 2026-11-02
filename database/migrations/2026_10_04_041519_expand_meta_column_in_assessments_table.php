<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE assessments MODIFY meta LONGTEXT NULL'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE assessments MODIFY meta VARCHAR(255) NULL'
        );
    }
};