<?php
/**
 * Simple environment loader for ClassTrack.
 */

function loadEnvFile($path) {
    if (!file_exists($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if (
            strlen($value) >= 2 &&
            (($value[0] === '"' && substr($value, -1) === '"') ||
             ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

function envValue($key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    return $value;
}

function appBasePath() {
    $appUrl = envValue('APP_URL', 'http://localhost/classtrack');
    $path = parse_url($appUrl, PHP_URL_PATH);

    if (!$path || $path === '/') {
        return '';
    }

    return rtrim($path, '/');
}

function appPath($path = '') {
    return appBasePath() . '/' . ltrim($path, '/');
}

loadEnvFile(__DIR__ . '/../.env');
?>
