<?php

namespace NexusPlugin\CustomMenu\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use NexusPlugin\CustomMenu\Models\MenuItem;
use NexusPlugin\CustomMenu\Support\MenuRenderer;
use PHPUnit\Framework\TestCase;

class MenuRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB();
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'mysql');
        $db->setAsGlobal();
        $db->bootEloquent();

        DB::schema('mysql')->create('plugin_custom_menu_items', function ($table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->string('text', 100);
            $table->string('url', 500)->default('');
            $table->string('target', 20)->default('_self');
            $table->string('style', 500)->default('');
            $table->integer('sort')->default(0);
            $table->integer('min_class')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function test_it_renders_visible_nested_menu(): void
    {
        global $CURUSER;

        $CURUSER = ['class' => 1];
        $_SERVER['SCRIPT_NAME'] = '/torrents.php';

        $parent = MenuItem::query()->create([
            'text' => 'Torrents',
            'url' => 'torrents.php',
            'sort' => 100,
            'enabled' => true,
        ]);
        MenuItem::query()->create([
            'parent_id' => $parent->id,
            'text' => 'Upload',
            'url' => 'upload.php',
            'sort' => 90,
            'enabled' => true,
        ]);
        MenuItem::query()->create([
            'text' => 'Staff',
            'url' => 'staff.php',
            'sort' => 80,
            'min_class' => 13,
            'enabled' => true,
        ]);

        $html = (new MenuRenderer())->render();

        $this->assertStringContainsString('id="mainmenu" class="menu"', $html);
        $this->assertStringContainsString('<li class="selected"><a href="torrents.php"', $html);
        $this->assertStringContainsString('<a href="upload.php"', $html);
        $this->assertStringNotContainsString('staff.php', $html);
    }

    public function test_it_renders_current_language_text(): void
    {
        global $CURUSER;

        $CURUSER = ['class' => 1];
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        if (!function_exists('get_langfolder_cookie')) {
            eval('function get_langfolder_cookie() { return "chs"; }');
        }

        MenuItem::query()->create([
            'text' => [
                'en' => 'Home',
                'chs' => '首页',
                'cht' => '首頁',
            ],
            'url' => 'index.php',
            'sort' => 100,
            'enabled' => true,
        ]);

        $html = (new MenuRenderer())->render();

        $this->assertStringContainsString('>首页</a>', $html);
        $this->assertStringNotContainsString('>Home</a>', $html);
    }
}
