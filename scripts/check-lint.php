#!/usr/bin/env php
<?php

$root = dirname(__DIR__);
$directories = [
    $root . '/app',
    $root . '/config',
    $root . '/public',
    $root . '/scripts',
];

$files = [];

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();

            if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $files[] = $path;
        }
    }
}

if (empty($files)) {
    echo "No PHP files found to lint." . PHP_EOL;
    exit(0);
}

$errors = [];

foreach ($files as $file) {
    $output = [];
    $exitCode = 0;
    exec(sprintf('php -l %s', escapeshellarg($file)), $output, $exitCode);

    if ($exitCode !== 0) {
        $errors[] = [
            'file' => $file,
            'output' => $output,
        ];
    }
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        fwrite(STDERR, "Lint error in {$error['file']}" . PHP_EOL);
        foreach ($error['output'] as $line) {
            fwrite(STDERR, $line . PHP_EOL);
        }
        fwrite(STDERR, PHP_EOL);
    }

    exit(1);
}

echo "All PHP files linted successfully." . PHP_EOL;
