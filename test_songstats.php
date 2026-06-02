<?php
function getBPM($spotify_id, $idUnique = '') {
    // Étape 1 : Résoudre via songstats.com/t/
    $ch = curl_init('https://songstats.com/t/' . $spotify_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $response = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if (!preg_match('/songstats\.com\/track\/([a-z0-9]+)\//i', $finalUrl, $m)) {
        return "❌ Songstats ID non trouvé";
    }
    $songstats_id = $m[1];

    // Étape 2 : Récupérer le BPM
    $params = 'source=overview' . ($idUnique ? '&idUnique=' . $idUnique : '');
    $ch2 = curl_init('https://data.songstats.com/api/v1/analytics_track/' . $songstats_id . '/top?' . $params);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Origin: https://songstats.com', 'Referer: https://songstats.com/']);
    curl_setopt($ch2, CURLOPT_ENCODING, '');
    $data = json_decode(curl_exec($ch2), true);
    curl_close($ch2);

    if (isset($data['overviewInfo']['audioFeatureData']['summaryItems'])) {
        $result = "✅ $songstats_id — ";
        foreach ($data['overviewInfo']['audioFeatureData']['summaryItems'] as $item) {
            $result .= $item['name'] . ': ' . $item['displayValue'] . ' | ';
        }
        return $result;
    }
    return "❌ BPM non trouvé (songstats_id: $songstats_id)";
}

$spotify_ids = [
    '3QHMxEOAGD51PDlbFPHLyJ', // Vivir Mi Vida
    '7scFxt9VhL4FJwuPSfRlfN', // Test Tunebat
];

echo "=== SANS idUnique ===\n";
foreach ($spotify_ids as $id) {
    echo $id . ': ' . getBPM($id) . "\n";
}

echo "\n=== AVEC idUnique ===\n";
foreach ($spotify_ids as $id) {
    echo $id . ': ' . getBPM($id, 'sb042k69') . "\n";
}
