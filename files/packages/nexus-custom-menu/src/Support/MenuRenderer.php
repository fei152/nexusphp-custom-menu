<?php

namespace NexusPlugin\CustomMenu\Support;

use Illuminate\Support\Collection;
use NexusPlugin\CustomMenu\Models\MenuItem;

class MenuRenderer
{
    private string $currentPath;

    public function __construct()
    {
        $this->currentPath = ltrim((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    }

    public function render(): string
    {
        $groupedItems = $this->visibleItems();

        if ($groupedItems->get(0, collect())->isEmpty()) {
            return '';
        }

        return $this->renderStyle() . $this->renderList($groupedItems, 0, true);
    }

    private function visibleItems(): Collection
    {
        global $CURUSER;

        $userClass = (int) ($CURUSER['class'] ?? 0);

        return MenuItem::query()
            ->where('enabled', true)
            ->where('min_class', '<=', $userClass)
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (MenuItem $item) => (int) $item->parent_id);
    }

    private function renderList(Collection $groupedItems, int $parentId, bool $isRoot = false): string
    {
        $items = $groupedItems->get($parentId, collect());

        if ($items->isEmpty()) {
            return '';
        }

        $attributes = $isRoot ? ' id="mainmenu" class="menu nexus-custom-menu"' : '';
        $html = "<ul{$attributes}>";

        foreach ($items as $item) {
            $children = $this->renderList($groupedItems, (int) $item->id);
            $html .= $this->renderItem($item, $children);
        }

        return $html . '</ul>';
    }

    private function renderItem(MenuItem $item, string $children): string
    {
        $classes = [];

        if ($this->isSelected($item)) {
            $classes[] = 'selected';
        }

        if ($children !== '') {
            $classes[] = 'has-children';
        }

        $liClass = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
        $url = e($item->url ?: '#');
        $text = e($item->display_text);
        $target = e($item->target ?: MenuItem::TARGET_SELF);
        $style = $item->style ? ' style="' . e($item->style) . '"' : '';

        return sprintf(
            '<li%s><a href="%s" target="%s"%s>%s</a>%s</li>',
            $liClass,
            $url,
            $target,
            $style,
            $text,
            $children
        );
    }

    private function renderStyle(): string
    {
        return <<<'HTML'
<style>
ul.nexus-custom-menu,
ul.nexus-custom-menu ul {
    list-style: none;
    margin-left: 0;
    padding-left: 0;
}
ul.nexus-custom-menu {
    overflow: visible;
}
ul.nexus-custom-menu li {
    display: inline-block;
    position: relative;
    vertical-align: top;
}
ul.nexus-custom-menu li ul {
    background: #fffdf2;
    border: 1px solid #b87700;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .18);
    display: none;
    left: 0;
    margin: 1px 0 0;
    min-width: 130px;
    padding: 2px 0;
    position: absolute;
    text-align: left;
    top: 100%;
    white-space: nowrap;
    z-index: 3000;
}
ul.nexus-custom-menu li:hover > ul,
ul.nexus-custom-menu li:focus-within > ul {
    display: block;
}
ul.nexus-custom-menu li ul li {
    display: block;
    margin: 0;
}
ul.nexus-custom-menu li ul li a {
    background: #fffdf2 !important;
    border: 0 !important;
    color: #2d2b2b !important;
    display: block !important;
    font: bold 12px verdana !important;
    margin: 0 !important;
    min-width: 130px;
    padding: 6px 12px !important;
    position: static !important;
    text-align: left !important;
    text-decoration: none !important;
    top: auto !important;
}
ul.nexus-custom-menu li ul li a:hover {
    background: #f3a52a !important;
    color: #000 !important;
    text-decoration: none !important;
}
ul.nexus-custom-menu li ul li ul {
    margin-left: 1px;
    margin-top: -3px;
    left: 100%;
    top: 0;
}
ul.nexus-custom-menu li.has-children > a::after {
    content: "\25BE";
    font-size: 9px;
    margin-left: 4px;
}
ul.nexus-custom-menu li ul li.has-children > a::after {
    content: "\25B8";
    float: right;
    margin-left: 8px;
}
</style>
HTML;
    }

    private function isSelected(MenuItem $item): bool
    {
        $urlPath = parse_url((string) $item->url, PHP_URL_PATH);

        if (!$urlPath) {
            return false;
        }

        return basename($urlPath) === basename($this->currentPath);
    }
}
