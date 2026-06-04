<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plugin_custom_menu_items')) {
            return;
        }

        DB::statement('ALTER TABLE `plugin_custom_menu_items` MODIFY `text` TEXT NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('plugin_custom_menu_items')) {
            return;
        }

        DB::statement('ALTER TABLE `plugin_custom_menu_items` MODIFY `text` VARCHAR(100) NOT NULL');
    }
};
