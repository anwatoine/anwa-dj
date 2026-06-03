<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://dj.anwadance.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$body = json_decode(file_get_contents('php://input'), true);
$spotify_ids = $body['ids'] ?? [];

if (empty($spotify_ids)) {
    echo json_encode(['error' => 'No IDs provided']);
    exit;
}

$cache_file = __DIR__ . '/bpm_cache.json';
$cache = file_exists($cache_file) ? json_decode(file_get_contents($cache_file), true) : [];
$results = [];
$new_entries = false;

foreach ($spotify_ids as $spotify_id) {
    // Vérifier le cache
    if (isset($cache[$spotify_id])) {
        $results[$spotify_id] = $cache[$spotify_id];
        continue;
    }

    // Étape 1 : Résoudre ID Spotify → Songstats ID
    $ch = curl_init('https://songstats.com/t/' . $spotify_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if (!preg_match('/songstats\.com\/track\/([a-z0-9]+)\//i', $finalUrl, $m)) {
        $results[$spotify_id] = ['bpm' => null, 'key' => null, 'error' => 'Not found'];
        continue;
    }
    $songstats_id = $m[1];

    // Étape 2 : Récupérer BPM
    $ch2 = curl_init('https://data.songstats.com/api/v1/analytics_track/' . $songstats_id . '/top?source=overview');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Origin: https://songstats.com', 'Referer: https://songstats.com/']);
    curl_setopt($ch2, CURLOPT_ENCODING, '');
    $data = json_decode(curl_exec($ch2), true);
    curl_close($ch2);

    $bpm = null; $key = null; $duration = null; $camelot = null;

// Table de conversion Key → Camelot
$camelotMap = [
    'C' => '8B', 'C#' => '3B', 'Db' => '3B',
    'D' => '10B', 'D#' => '5B', 'Eb' => '5B',
    'E' => '12B', 'F' => '7B', 'F#' => '2B', 'Gb' => '2B',
    'G' => '9B', 'G#' => '4B', 'Ab' => '4B',
    'A' => '11B', 'A#' => '6B', 'Bb' => '6B', 'B' => '1B',
    // Mineures
    'Cm' => '5A', 'C#m' => '12A', 'Dbm' => '12A',
    'Dm' => '7A', 'D#m' => '2A', 'Ebm' => '2A',
    'Em' => '9A', 'Fm' => '4A', 'F#m' => '11A', 'Gbm' => '11A',
    'Gm' => '6A', 'G#m' => '1A', 'Abm' => '1A',
    'Am' => '8A', 'A#m' => '3A', 'Bbm' => '3A', 'Bm' => '10A',
];
    if (isset($data['overviewInfo']['audioFeatureData']['summaryItems'])) {
        foreach ($data['overviewInfo']['audioFeatureData']['summaryItems'] as $item) {
            if ($item['key'] === 'tempo') $bpm = $item['value'];
            if ($item['key'] === 'key') $key = $item['value'];
            if ($item['key'] === 'duration') $duration = $item['value'];
        }
        // Convertir Key en Camelot
        if ($key && isset($camelotMap[$key])) {
            $camelot = $camelotMap[$key];
        }
    }

    $entry = ['bpm' => $bpm, 'key' => $key, 'camelot' => $camelot, 'duration' => $duration, 'songstats_id' => $songstats_id];
    $results[$spotify_id] = $entry;
    $cache[$spotify_id] = $entry;
    $new_entries = true;

    // Petit délai pour éviter le rate limiting
    usleep(200000); // 200ms
}

// Sauvegarder le cache
if ($new_entries) {
    file_put_contents($cache_file, json_encode($cache));
}

echo json_encode(['success' => true, 'results' => $results]);
