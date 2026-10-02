<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('drag_drop_items', function (Blueprint $table) {
            if (!Schema::hasColumn('drag_drop_items', 'display_name')) {
                $table->string('display_name', 100)->nullable()->after('component_key');
            }

            if (!Schema::hasColumn('drag_drop_items', 'item_type')) {
                $table->string('item_type', 20)->default('hardware')->after('display_name');
            }

            if (!Schema::hasColumn('drag_drop_items', 'quantity')) {
                $table->unsignedSmallInteger('quantity')->default(1)->after('item_type');
            }

            if (!Schema::hasColumn('drag_drop_items', 'target_zone')) {
                $table->string('target_zone', 20)->nullable()->after('action');
            }
        });

        if (Schema::hasTable('drag_drop_attempt_steps')) {
            Schema::table('drag_drop_attempt_steps', function (Blueprint $table) {
                if (!Schema::hasColumn('drag_drop_attempt_steps', 'performed_action')) {
                    $table->string('performed_action', 20)->nullable()->after('expected_action');
                }

                if (!Schema::hasColumn('drag_drop_attempt_steps', 'source_zone')) {
                    $table->string('source_zone', 20)->nullable()->after('performed_component_key');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('drag_drop_attempt_steps')) {
            Schema::table('drag_drop_attempt_steps', function (Blueprint $table) {
                $columns = [];

                foreach ([
                    'performed_action',
                    'source_zone',
                ] as $column) {
                    if (Schema::hasColumn('drag_drop_attempt_steps', $column)) {
                        $columns[] = $column;
                    }
                }

                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::table('drag_drop_items', function (Blueprint $table) {
            $columns = [];

            foreach ([
                'display_name',
                'item_type',
                'quantity',
                'target_zone',
            ] as $column) {
                if (Schema::hasColumn('drag_drop_items', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
