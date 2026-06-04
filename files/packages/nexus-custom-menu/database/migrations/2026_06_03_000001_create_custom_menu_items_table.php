<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_custom_menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->text('text');
            $table->string('url', 500)->default('');
            $table->string('target', 20)->default('_self');
            $table->string('style', 500)->default('');
            $table->integer('sort')->default(0);
            $table->integer('min_class')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('enabled');
            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_custom_menu_items');
    }
};
