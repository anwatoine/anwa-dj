<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://dj.anwadance.com');
header('Access-Control-Allow-Methods: POST');
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

$body = json_decode(file_get_contents('php://input'), true);
$grant_type = $body['grant_type'] ?? '';
$client_id = '8d38f9416611475b9108189802c111f8';
$client_secret = '576ac534e4ee447fb5b1c1184307c025';
$redirect_uri = 'https://dj.anwadance.com';

$params = [
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri'  => $redirect_uri,
];

if ($grant_type === 'authorization_code') {
    $params['grant_type'] = 'authorization_code';
    $params['code'] = $body['code'] ?? '';
} elseif ($grant_type === 'refresh_token') {
    $params['grant_type'] = 'refresh_token';
    $params['refresh_token'] = $body['refresh_token'] ?? '';
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid grant_type']);
    exit;
}

$ch = curl_init('https://accounts.spotify.com/api/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
