<?php
// Test Songstats API avec différentes approches
$spotify_id = '3QHMxEOAGD51PDlbFPHLyJ'; // Vivir Mi Vida - Marc Anthony
$isrc = 'ARF060500259';
$songstats_id = '09b3tl47';

$tests = [
    'API track by Spotify ID' => 'https://api.songstats.com/enterprise/v1/tracks/info?spotify_track_id=' . $spotify_id,
    'API track by ISRC' => 'https://api.songstats.com/enterprise/v1/tracks/info?isrc=' . $isrc,
    'API track by ID' => 'https://api.songstats.com/enterprise/v1/tracks/' . $songstats_id,
    'Public track' => 'https://songstats.com/api/tracks/' . $songstats_id,
    'Next data' => 'https://songstats.com/_next/data/tracks/' . $songstats_id . '.json',
];

foreach ($tests as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Referer: https://songstats.com/',
    ]);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label\n";
    echo "Code: $code | Length: " . strlen($response) . "\n";
    if ($code === 200 && strlen($response) > 10) {
        echo "SUCCÈS ! Réponse: " . substr($response, 0, 400) . "\n";
    }
    echo "---\n";
}
