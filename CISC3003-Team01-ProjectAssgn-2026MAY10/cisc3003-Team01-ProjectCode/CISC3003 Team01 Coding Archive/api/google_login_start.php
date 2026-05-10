<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../includes/oauth_config.php';

$oauthConfig = loadGoogleOAuthConfig();
$clientId = $oauthConfig['client_id'];
$clientSecret = $oauthConfig['client_secret'];
$redirectUri = resolveGoogleRedirectUri($oauthConfig);

if ($clientId === '' || $clientSecret === '') {
    $msg = urlencode('Google login is not configured yet. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET first.');
    header("Location: ../login.php?oauth_error={$msg}");
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$query = http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
], '', '&', PHP_QUERY_RFC3986);

header("Location: https://accounts.google.com/o/oauth2/v2/auth?{$query}");
exit;
