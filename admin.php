<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$token_file = __DIR__ . '/spotify_token.json';

// Stocker le token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    
    if (($body['action'] ?? '') === 'store_token' && !empty($body['token'])) {
        $data = [
            'token' => $body['token'],
            'stored_at' => time(),
            'expires_at' => time() + 3600
        ];
        file_put_contents($token_file, json_encode($data));
        echo json_encode(['success' => true, 'message' => 'Token stocké']);
        exit;
    }
}

// Récupérer le token
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($token_file)) {
        $data = json_decode(file_get_contents($token_file), true);
        if ($data['expires_at'] > time()) {
            echo json_encode([
                'success' => true,
                'token' => $data['token'],
                'expires_in' => $data['expires_at'] - time()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Token expiré']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun token stocké']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
