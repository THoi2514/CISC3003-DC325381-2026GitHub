<?php
declare(strict_types=1);

/**
 * Load Google OAuth config from:
 * 1) process/server environment variables
 * 2) project root .env file (fallback)
 */
function loadGoogleOAuthConfig(): array
{
    $config = [
        'client_id' => trim((string)(getenv('GOOGLE_CLIENT_ID') ?: ($_SERVER['GOOGLE_CLIENT_ID'] ?? ''))),
        'client_secret' => trim((string)(getenv('GOOGLE_CLIENT_SECRET') ?: ($_SERVER['GOOGLE_CLIENT_SECRET'] ?? ''))),
        'redirect_uri' => trim((string)(getenv('GOOGLE_REDIRECT_URI') ?: ($_SERVER['GOOGLE_REDIRECT_URI'] ?? ''))),
    ];

    if ($config['client_id'] !== '' && $config['client_secret'] !== '') {
        return $config;
    }

    $envPath = dirname(__DIR__) . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return $config;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $config;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");

        if ($key === 'GOOGLE_CLIENT_ID' && $config['client_id'] === '') {
            $config['client_id'] = $value;
        } elseif ($key === 'GOOGLE_CLIENT_SECRET' && $config['client_secret'] === '') {
            $config['client_secret'] = $value;
        } elseif ($key === 'GOOGLE_REDIRECT_URI' && $config['redirect_uri'] === '') {
            $config['redirect_uri'] = $value;
        }
    }

    return $config;
}

/**
 * Resolve redirect URI for current runtime host.
 * In local development, we prefer current host callback to avoid cross-domain state mismatch.
 */
function resolveGoogleRedirectUri(array $oauthConfig, string $callbackScript = 'google_login_callback.php'): string
{
    $configured = trim((string)($oauthConfig['redirect_uri'] ?? ''));
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '/api');
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $rawPath = "{$basePath}/{$callbackScript}";
    $pathSegments = array_values(array_filter(explode('/', trim($rawPath, '/')), static fn(string $seg): bool => $seg !== ''));
    $encodedPath = '/' . implode('/', array_map('rawurlencode', $pathSegments));
    $runtimeRedirect = "{$scheme}://{$host}{$encodedPath}";

    if ($configured === '') {
        return $runtimeRedirect;
    }

    $runtimeHost = strtolower((string)parse_url($runtimeRedirect, PHP_URL_HOST));
    $configuredHost = strtolower((string)parse_url($configured, PHP_URL_HOST));
    $isLocalRuntime = in_array($runtimeHost, ['localhost', '127.0.0.1'], true);

    if ($isLocalRuntime && $configuredHost !== $runtimeHost) {
        return $runtimeRedirect;
    }

    // Domain changed: avoid sending users back to stale host from old env vars.
    if ($runtimeHost !== '' && $configuredHost !== '' && $configuredHost !== $runtimeHost) {
        return $runtimeRedirect;
    }

    return $configured;
}
