<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://dj.anwadance.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$client_id = '8d38f9416611475b9108189802c111f8';
$client_secret = '576ac534e4ee447fb5b1c1184307c025';
$redirect_uri = 'https://dj.anwadance.com';
$basic = base64_encode($client_id . ':' . $client_secret);

$body = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? $body['grant_type'] ?? '';

// ── Échange code OAuth (pour créer playlists) ─────────────────────────────
if ($action === 'authorization_code') {
    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type'   => 'authorization_code',
        'code'         => $body['code'] ?? '',
        'redirect_uri' => $redirect_uri,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . $basic
    ]);
    echo curl_exec($ch);
    curl_close($ch);
    exit;
}

// ── Refresh token OAuth ───────────────────────────────────────────────────
if ($action === 'refresh_token') {
    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type'    => 'refresh_token',
        'refresh_token' => $body['refresh_token'] ?? '',
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . $basic
    ]);
    echo curl_exec($ch);
    curl_close($ch);
    exit;
}

// ── Client Credentials (pour lire les playlists publiques) ───────────────
if ($action === 'client_credentials') {
    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'client_credentials',
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . $basic
    ]);
    echo curl_exec($ch);
    curl_close($ch);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
