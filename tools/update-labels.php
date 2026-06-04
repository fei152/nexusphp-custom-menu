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

foreach ($replacements as $relativePath => $pairs) {
    $path = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($path)) {
        continue;
    }

    $content = file_get_contents($path);
    $updated = str_replace(array_keys($pairs), array_values($pairs), $content);

    if ($updated !== $content) {
        file_put_contents($path, $updated);
        echo "updated {$relativePath}\n";
    }
}
