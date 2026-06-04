<?php

namespace NexusPlugin\CustomMenu;

use App\Models\Setting;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Tabs\Tab;
use Nexus\Plugin\BasePlugin;
use NexusPlugin\CustomMenu\Support\MenuRenderer;

class Repository extends BasePlugin
{
    public const ID = 'custom-menu';
    public const VERSION = '1.0.0';
    public const COMPATIBLE_NP_VERSION = '1.10.2';

    public function install(): void
    {
        $this->runMigrations(__DIR__ . '/../database/migrations');
        $this->seedDefaults();
    }

    public function uninstall(): void
    {
        $this->runMigrations(__DIR__ . '/../database/migrations', true);
    }

    public function boot(): void
    {
        $this->guardCurrentRequest();
        add_filter('nexus_menu', [$this, 'renderMenu']);
        add_filter('nexus_setting_tabs', [$this, 'addSettingTab']);
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function renderMenu(): string
    {
        if (!$this->isCustomMenuEnabled()) {
            return '';
        }

        return (new MenuRenderer())->render();
    }

    public function addSettingTab(array $tabs): array
    {
        $tabs[] = Tab::make(__('label.menu.label'))
            ->id('menu')
            ->schema([
                Radio::make('menu.enable')
                    ->options($this->yesNoOptions())
                    ->inline(true)
                    ->label(__('label.enabled'))
                    ->helperText(__('label.menu.enable_help'))
                    ->default('yes'),
            ])
            ->columns(2);

        return $tabs;
    }

    private function guardCurrentRequest(): void
    {
        if ($this->shouldSkipGuard() || !$this->isCustomMenuEnabled()) {
            return;
        }

        $this->ensureCurrentUserLoaded();

        $userClass = (int) (get_user_class() ?: 0);

        $restrictedItem = Models\MenuItem::query()
            ->where('enabled', true)
            ->where('min_class', '>', 0)
            ->where('min_class', '>', $userClass)
            ->get()
            ->first(fn (Models\MenuItem $item) => $this->matchesCurrentRequest((string) $item->url));

        if ($restrictedItem) {
            $this->notFound();
        }
    }

    private function shouldSkipGuard(): bool
    {
        if (PHP_SAPI === 'cli' || (function_exists('isRunningInConsole') && isRunningInConsole())) {
            return true;
        }

        if (!defined('IN_NEXUS') || !IN_NEXUS) {
            return true;
        }

        $script = function_exists('nexus') ? nexus()->getScript() : '';

        return in_array($script, ['announce', 'scrape'], true);
    }

    private function ensureCurrentUserLoaded(): void
    {
        global $CURUSER;

        if (!empty($CURUSER) || !function_exists('userlogin')) {
            return;
        }

        \Nexus\Database\NexusDB::getInstance()->autoConnect();
        userlogin();
    }

    private function matchesCurrentRequest(string $menuUrl): bool
    {
        $menuParts = parse_url($menuUrl);

        if (empty($menuParts['path'])) {
            return false;
        }

        if (!empty($menuParts['host']) && strcasecmp($menuParts['host'], (string) ($_SERVER['HTTP_HOST'] ?? '')) !== 0) {
            return false;
        }

        $menuPath = basename($menuParts['path']);
        $currentPath = basename((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: ($_SERVER['SCRIPT_NAME'] ?? '')));

        if ($menuPath === '' || $menuPath !== $currentPath) {
            return false;
        }

        if (empty($menuParts['query'])) {
            return true;
        }

        parse_str($menuParts['query'], $menuQuery);
        parse_str((string) ($_SERVER['QUERY_STRING'] ?? ''), $currentQuery);

        return $this->queryContains($currentQuery, $menuQuery);
    }

    private function queryContains(array $currentQuery, array $menuQuery): bool
    {
        foreach ($menuQuery as $key => $expectedValue) {
            if (!array_key_exists($key, $currentQuery)) {
                return false;
            }

            $actualValue = $currentQuery[$key];

            if (is_array($expectedValue)) {
                if (!is_array($actualValue) || !$this->queryContains($actualValue, $expectedValue)) {
                    return false;
                }

                continue;
            }

            if (is_array($actualValue)) {
                return false;
            }

            if ((string) $actualValue !== (string) $expectedValue) {
                return false;
            }
        }

        return true;
    }

    private function notFound(): never
    {
        if (function_exists('httperr')) {
            httperr();
        }

        header('HTTP/1.1 404 Not found');
        print("<h1>Not Found</h1>\n");
        exit();
    }

    private function isCustomMenuEnabled(): bool
    {
        return Setting::get('menu.enable', 'yes') === 'yes';
    }

    private function yesNoOptions(): array
    {
        return [
            'yes' => 'yes',
            'no' => 'no',
        ];
    }

    private function seedDefaults(): void
    {
        Setting::query()->updateOrCreate(
            ['name' => 'menu.enable'],
            [
                'value' => 'yes',
                'autoload' => 'yes',
            ]
        );

        $defaults = [
            ['text' => ['en' => 'Home', 'chs' => '首页', 'cht' => '首頁'], 'url' => 'index.php', 'sort' => 1000],
            ['text' => ['en' => 'Torrents', 'chs' => '种子', 'cht' => '種子'], 'url' => 'torrents.php', 'sort' => 900],
            ['text' => ['en' => 'Upload', 'chs' => '发布', 'cht' => '發布'], 'url' => 'upload.php', 'sort' => 800],
            ['text' => ['en' => 'Requests', 'chs' => '求种', 'cht' => '求種'], 'url' => 'viewrequests.php', 'sort' => 700],
            ['text' => ['en' => 'Top 10', 'chs' => '排行榜', 'cht' => '排行榜'], 'url' => 'topten.php', 'sort' => 600],
            ['text' => ['en' => 'Rules', 'chs' => '规则', 'cht' => '規則'], 'url' => 'rules.php', 'sort' => 500],
            ['text' => ['en' => 'FAQ', 'chs' => '常见问题', 'cht' => '常見問題'], 'url' => 'faq.php', 'sort' => 400],
        ];

        foreach ($defaults as $row) {
            Models\MenuItem::query()->firstOrCreate(
                ['parent_id' => 0, 'url' => $row['url']],
                $row + [
                    'target' => Models\MenuItem::TARGET_SELF,
                    'enabled' => true,
                    'min_class' => 0,
                ]
            );
        }
    }
}
