<?php
$idUnique = 'sb042k69';
$spotify_id = '3QHMxEOAGD51PDlbFPHLyJ'; // Vivir Mi Vida Marc Anthony

$tests = [
    'spotify ID direct' => 'https://data.songstats.com/api/v1/analytics_track/' . $spotify_id . '/top?source=spotify&idUnique=' . $idUnique,
    'search avec idUnique' => 'https://data.songstats.com/api/v1/search/search_all?q=Vivir+Mi+Vida+Marc+Anthony&excludedModels=RadioStation&idUnique=' . $idUnique,
    'resolve spotify' => 'https://data.songstats.com/api/v1/tracks/resolve/spotify/' . $spotify_id . '?idUnique=' . $idUnique,
    'open spotify source' => 'https://data.songstats.com/api/v1/open/spotify/' . $spotify_id . '?idUnique=' . $idUnique,
];

foreach ($tests as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Origin: https://songstats.com', 'Referer: https://songstats.com/']);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label — Code: $code | Length: " . strlen($response) . "\n";
    $data = json_decode($response, true);
    if ($data && strlen($response) > 50) {
        if (isset($data['overviewInfo']['audioFeatureData']['summaryItems'])) {
            echo "✅ BPM TROUVÉ !\n";
            foreach ($data['overviewInfo']['audioFeatureData']['summaryItems'] as $item) {
                echo "  " . $item['name'] . ': ' . $item['displayValue'] . "\n";
            }
        } elseif (isset($data['results'])) {
            echo "  Résultats: " . count($data['results']) . " items\n";
            foreach (array_slice($data['results'], 0, 2) as $r) {
                echo "  → " . ($r['trackName'] ?? $r['name'] ?? 'N/A') . " | idUnique: " . ($r['idUnique'] ?? 'N/A') . "\n";
            }
        } else {
            echo "  " . substr($response, 0, 150) . "\n";
        }
    }
    echo "---\n";
}
