<?php
$api_key = '73055c779emsh071a5199f63ddb8p1eca73jsn0d16bc7c9974';

// Test 1 : par nom + artiste
$url1 = 'https://track-analysis.p.rapidapi.com/pktx/analysis?song=' . urlencode('Loco') . '&artist=' . urlencode('DJ Husky');

// Test 2 : par ID Spotify
$url2 = 'https://track-analysis.p.rapidapi.com/pktx/analysis?spotify_id=1EMci9LycCKIO2X58jZPJ6';

$headers = [
    'Content-Type: application/json',
    'x-rapidapi-host: track-analysis.p.rapidapi.com',
    'x-rapidapi-key: ' . $api_key,
];

foreach (['Par nom+artiste' => $url1, 'Par Spotify ID' => $url2] as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label — Code: $code\n";
    if ($code === 200) {
        $data = json_decode($response, true);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo $response . "\n";
    }
    echo "---\n";
}
