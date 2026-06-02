<?php
$spotify_id = '3QHMxEOAGD51PDlbFPHLyJ';
$songstats_id = '09b3tl47';

$tests = [
    'data API track' => 'https://data.songstats.com/api/v1/tracks/' . $songstats_id,
    'data API spotify' => 'https://data.songstats.com/api/v1/tracks/spotify/' . $spotify_id,
    'data API insights' => 'https://data.songstats.com/api/v1/tracks/' . $songstats_id . '/insights',
    'data API audio' => 'https://data.songstats.com/api/v1/tracks/' . $songstats_id . '/audio-features',
    'data API search' => 'https://data.songstats.com/api/v1/search?q=Vivir+Mi+Vida&type=track',
];

foreach ($tests as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Origin: https://songstats.com',
        'Referer: https://songstats.com/',
    ]);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label\n";
    echo "Code: $code | Length: " . strlen($response) . "\n";
    if ($code === 200 && strlen($response) > 10) {
        echo "SUCCÈS: " . substr($response, 0, 300) . "\n";
    }
    echo "---\n";
}
