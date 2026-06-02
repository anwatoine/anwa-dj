<?php
$spotify_id = '3QHMxEOAGD51PDlbFPHLyJ';

$tests = [
    'search by spotify' => 'https://data.songstats.com/api/v1/search?q=' . $spotify_id . '&type=track',
    'search by name' => 'https://data.songstats.com/api/v1/search?q=Vivir+Mi+Vida+Marc+Anthony&type=track',
    'resolve spotify' => 'https://data.songstats.com/api/v1/tracks/resolve?spotifyId=' . $spotify_id,
    'resolve isrc' => 'https://data.songstats.com/api/v1/tracks/resolve?isrc=USRC11200609',
    'track by spotify' => 'https://data.songstats.com/api/v1/tracks?spotifyTrackId=' . $spotify_id,
    'spotify lookup' => 'https://data.songstats.com/api/v1/lookup?spotify_id=' . $spotify_id,
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

    echo "$label — Code: $code | Length: " . strlen($response) . "\n";
    if ($code === 200 && strlen($response) > 10) {
        echo "SUCCÈS: " . substr($response, 0, 300) . "\n";
    }
    echo "---\n";
}
