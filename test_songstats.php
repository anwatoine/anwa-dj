<?php
$spotify_id = '3QHMxEOAGD51PDlbFPHLyJ'; // Vivir Mi Vida Marc Anthony

$tests = [
    // Essayer d acceder directement via source spotify
    'source spotify' => 'https://data.songstats.com/api/v1/analytics_track/dgtqu35r/top?source=overview&idUnique=dgtqu35r',
    // Essayer de resoudre l ID Spotify vers Songstats
    'resolve via source' => 'https://songstats.com/track/dgtqu35r/vivir-mi-vida?source=spotify&spotifyId=' . $spotify_id,
    // Chercher via leur API de recherche interne
    'internal search' => 'https://data.songstats.com/api/v1/search/tracks?q=Vivir+Mi+Vida+Marc+Anthony&limit=5',
    'internal search2' => 'https://data.songstats.com/api/v1/tracks/search?q=Vivir+Mi+Vida&artist=Marc+Anthony',
    // Lookup direct par spotify ID
    'spotify direct' => 'https://data.songstats.com/api/v1/analytics_track/spotify/' . $spotify_id . '/top?source=overview',
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
    if ($code === 200 && strlen($response) > 50 && $response[0] === '{') {
        $data = json_decode($response, true);
        if (isset($data['overviewInfo']['audioFeatureData']['summaryItems'])) {
            foreach ($data['overviewInfo']['audioFeatureData']['summaryItems'] as $item) {
                echo "  " . $item['name'] . ': ' . $item['displayValue'] . "\n";
            }
        } else {
            echo "  " . substr($response, 0, 200) . "\n";
        }
    }
    echo "---\n";
}
