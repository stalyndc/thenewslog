#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Helpers\Encoding;

$failures = [];

$invalid = "Hello\xB1World";
$sanitized = Encoding::ensureUtf8($invalid);

$isUtf8 = function (string $value): bool {
    if (function_exists('mb_check_encoding')) {
        return mb_check_encoding($value, 'UTF-8');
    }

    return @preg_match('//u', $value) === 1;
};

if (!is_string($sanitized)) {
    $failures[] = 'Sanitized value should be a string.';
} elseif (!$isUtf8($sanitized)) {
    $failures[] = 'Sanitized value should be valid UTF-8.';
} elseif (strpos($sanitized, "\xB1") !== false) {
    $failures[] = 'Sanitized value should not contain original invalid byte.';
}

$unchanged = 'Clean string';
if (Encoding::ensureUtf8($unchanged) !== $unchanged) {
    $failures[] = 'Valid UTF-8 strings should remain unchanged.';
}

if (Encoding::ensureUtf8(null) !== null) {
    $failures[] = 'Null input should return null.';
}

if (Encoding::ensureUtf8('') !== '') {
    $failures[] = 'Empty strings should remain empty.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Encoding helper tests failed:" . PHP_EOL);
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . PHP_EOL);
    }
    exit(1);
}

echo "Encoding helper tests passed." . PHP_EOL;
