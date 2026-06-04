<?php

$projectRoot = $argv[1] ?? getcwd();

$replacements = [
    'resources/lang/zh_CN/label.php' => [
        "'min_class' => '最低可见等级'" => "'min_class' => '最低访问等级'",
    ],
    'resources/lang/zh_TW/label.php' => [
        "'min_class' => '最低可見等級'" => "'min_class' => '最低訪問等級'",
    ],
    'resources/lang/en/label.php' => [
        "'min_class' => 'Minimum visible class'" => "'min_class' => 'Minimum access class'",
    ],
];

$menuLabels = [
    'resources/lang/zh_CN/label.php' => [
        'text' => "'text' => '显示文本',",
        'menu' => "'menu' => '菜单',",
    ],
    'resources/lang/zh_TW/label.php' => [
        'text' => "'text' => '顯示文本',",
        'menu' => "'menu' => '菜單',",
    ],
    'resources/lang/en/label.php' => [
        'text' => "'text' => 'Text',",
        'menu' => "'menu' => 'Menu',",
    ],
];

function ensureMenuItemMenuLabel(string $content, array $labels): string
{
    $start = strpos($content, "'menu_item' => [");

    if ($start === false) {
        return $content;
    }

    $end = strpos($content, "    ],", $start);

    if ($end === false) {
        return $content;
    }

    $section = substr($content, $start, $end - $start);

    if (str_contains($section, $labels['menu'])) {
        return $content;
    }

    $updatedSection = str_replace($labels['text'], $labels['text'] . "\n        " . $labels['menu'], $section);

    if ($updatedSection === $section) {
        return $content;
    }

    return substr($content, 0, $start) . $updatedSection . substr($content, $end);
}

foreach ($replacements as $relativePath => $pairs) {
    $path = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($path)) {
        continue;
    }

    $content = file_get_contents($path);
    $updated = $content;

    foreach ($pairs as $search => $replace) {
        $updated = str_replace($search, $replace, $updated);
    }

    $updated = ensureMenuItemMenuLabel($updated, $menuLabels[$relativePath]);

    if ($updated !== $content) {
        file_put_contents($path, $updated);
        echo "updated {$relativePath}\n";
    }
}
