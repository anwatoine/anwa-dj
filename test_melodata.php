<?php
$api_key = 'melo_sk_bb4deca8bae45503cc05c69b6b262270d7644595f6d7377565a952a0ea1ce604';
$headers = [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json',
];

// ISRC de Loco - DJ Husky (récupéré via Songstats)
// On va d'abord chercher via search, puis par ISRC

$tests = [
    'Search Loco DJ Husky' => [
        'url' => 'https://melodata.voltenworks.com/api/v1/tracks/search?q=Loco+DJ+Husky&limit=5',
        'method' => 'GET'
    ],
    'Search Loco Montelier' => [
        'url' => 'https://melodata.voltenworks.com/api/v1/tracks/search?q=Loco+Montelier&limit=5',
        'method' => 'GET'
    ],
    'Features Vivir Mi Vida (ISRC connu)' => [
        'url' => 'https://melodata.voltenworks.com/api/v1/tracks/USRC11200609/features',
        'method' => 'GET'
    ],
    'Batch pre-analyze Loco' => [
        'url' => 'https://melodata.voltenworks.com/api/v1/tracks/batch/analyze',
        'method' => 'POST',
        'body' => json_encode(['isrcs' => ['COLA02300259', 'COLA02300260']])
    ],
];

foreach ($tests as $label => $test) {
    $ch = curl_init($test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($test['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $test['body'] ?? '{}');
    }
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label — Code: $code\n";
    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "---\n";
}
