<?php
$spotify_id = '3QHMxEOAGD51PDlbFPHLyJ'; // Vivir Mi Vida - Marc Anthony

$urls = [
    'API v1' => 'https://tunebat.com/api/tracks/' . $spotify_id,
    'API v2' => 'https://tunebat.com/api/v1/tracks/' . $spotify_id,
    'API search' => 'https://tunebat.com/api/tracks/search?id=' . $spotify_id,
    'Next API' => 'https://tunebat.com/_next/data/tracks/' . $spotify_id . '.json',
];

foreach ($urls as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json, */*',
        'Accept-Language: fr-FR,fr;q=0.9',
        'Referer: https://tunebat.com/',
        'X-Requested-With: XMLHttpRequest',
    ]);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "$label ($url)\n";
    echo "Code: $code | Length: " . strlen($response) . "\n";
    if ($code === 200 && strlen($response) > 0) {
        echo "RÉPONSE: " . substr($response, 0, 300) . "\n";
    }
    echo "---\n";
}
