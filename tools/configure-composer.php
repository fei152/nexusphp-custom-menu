<?php

$projectRoot = $argv[1] ?? getcwd();
$composerPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';

if (!is_file($composerPath)) {
    fwrite(STDERR, "composer.json not found: {$composerPath}\n");
    exit(1);
}

$json = json_decode(file_get_contents($composerPath), true);

if (!is_array($json)) {
    fwrite(STDERR, "Invalid composer.json: {$composerPath}\n");
    exit(1);
}

$json['autoload']['psr-4']['NexusPlugin\\CustomMenu\\'] = 'packages/nexus-custom-menu/src/';
$json['require']['xiaomlove/nexusphp-menu'] = '*';

$repo = [
    'type' => 'path',
    'url' => 'packages/nexus-custom-menu',
    'options' => [
        'symlink' => true,
    ],
];

$repositories = $json['repositories'] ?? [];
$found = false;

if (array_is_list($repositories)) {
    foreach ($repositories as $existing) {
        if (is_array($existing) && ($existing['url'] ?? '') === $repo['url']) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        $repositories[] = $repo;
    }
} else {
    foreach ($repositories as $existing) {
        if (is_array($existing) && ($existing['url'] ?? '') === $repo['url']) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        $repositories['nexusphp-menu'] = $repo;
    }
}

$json['repositories'] = $repositories;

file_put_contents(
    $composerPath,
    json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
);

echo "composer.json configured for xiaomlove/nexusphp-menu\n";
