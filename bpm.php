<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$body = json_decode(file_get_contents('php://input'), true);
$spotify_ids = $body['ids'] ?? [];
$track_info = $body['track_info'] ?? []; // titre + artiste pour le log

if (empty($spotify_ids)) { echo json_encode(['error' => 'No IDs provided']); exit; }

// Augmenter le timeout PHP
set_time_limit(120);
ini_set('max_execution_time', 120);

// Limiter à 20 IDs par appel pour éviter les timeouts
$spotify_ids = array_slice($spotify_ids, 0, 20);

$manual_file  = __DIR__ . '/bpm_manual.json';
$cache_file   = __DIR__ . '/bpm_cache.json';
$missing_file = __DIR__ . '/bpm_missing.json';

$manual  = file_exists($manual_file)  ? json_decode(file_get_contents($manual_file), true)  : [];
$cache   = file_exists($cache_file)   ? json_decode(file_get_contents($cache_file), true)    : [];
$missing = file_exists($missing_file) ? json_decode(file_get_contents($missing_file), true)  : [];

// Table de conversion Key → Camelot
$camelotMap = [
    'C' => '8B', 'C#' => '3B', 'Db' => '3B', 'D' => '10B', 'D#' => '5B', 'Eb' => '5B',
    'E' => '12B', 'F' => '7B', 'F#' => '2B', 'Gb' => '2B', 'G' => '9B', 'G#' => '4B',
    'Ab' => '4B', 'A' => '11B', 'A#' => '6B', 'Bb' => '6B', 'B' => '1B',
    'Cm' => '5A', 'C#m' => '12A', 'Dbm' => '12A', 'Dm' => '7A', 'D#m' => '2A', 'Ebm' => '2A',
    'Em' => '9A', 'Fm' => '4A', 'F#m' => '11A', 'Gbm' => '11A', 'Gm' => '6A', 'G#m' => '1A',
    'Abm' => '1A', 'Am' => '8A', 'A#m' => '3A', 'Bbm' => '3A', 'Bm' => '10A',
    // MeloData format
    'C major' => '8B', 'C minor' => '5A', 'D major' => '10B', 'D minor' => '7A',
    'E major' => '12B', 'E minor' => '9A', 'F major' => '7B', 'F minor' => '4A',
    'G major' => '9B', 'G minor' => '6A', 'A major' => '11B', 'A minor' => '8A',
    'B major' => '1B', 'B minor' => '10A',
    'C# major' => '3B', 'C# minor' => '12A', 'D# major' => '5B', 'D# minor' => '2A',
    'F# major' => '2B', 'F# minor' => '11A', 'G# major' => '4B', 'G# minor' => '1A',
    'A# major' => '6B', 'A# minor' => '3A',
];

function getSongstats($spotify_id, $camelotMap) {
    $ch = curl_init('https://songstats.com/t/' . $spotify_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if (!preg_match('/songstats\.com\/track\/([a-z0-9]+)\//i', $finalUrl, $m)) return null;
    $songstats_id = $m[1];

    $ch2 = curl_init('https://data.songstats.com/api/v1/analytics_track/' . $songstats_id . '/top?source=overview');
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Origin: https://songstats.com', 'Referer: https://songstats.com/'],
        CURLOPT_ENCODING => '',
    ]);
    $data = json_decode(curl_exec($ch2), true);
    curl_close($ch2);

    $bpm = null; $key = null; $camelot = null; $isrc = null;
    if (isset($data['overviewInfo']['audioFeatureData']['summaryItems'])) {
        foreach ($data['overviewInfo']['audioFeatureData']['summaryItems'] as $item) {
            if ($item['key'] === 'tempo') $bpm = $item['value'];
            if ($item['key'] === 'key') $key = $item['value'];
        }
        if ($key && isset($camelotMap[$key])) $camelot = $camelotMap[$key];
    }
    // Récupérer ISRC
    if (isset($data['overviewInfo']['infoArray'])) {
        foreach ($data['overviewInfo']['infoArray'] as $info) {
            if ($info['name'] === 'ISRCs:' && !empty($info['data'][0]['text'])) {
                $isrc = $info['data'][0]['text'];
            }
        }
    }
    if (!$bpm) return null;
    return ['bpm' => $bpm, 'key' => $key, 'camelot' => $camelot, 'isrc' => $isrc, 'source' => 'songstats'];
}

function getMelodata($isrc, $api_key, $camelotMap) {
    if (!$isrc) return null;
    $ch = curl_init('https://melodata.voltenworks.com/api/v1/tracks/' . urlencode($isrc) . '/features');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_key, 'Content-Type: application/json'],
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 202) {
        // En cours d'analyse - pas facturé, on réessaie plus tard
        return null;
    }
    $data = json_decode($response, true);
    if ($code === 200 && isset($data['data']['features'])) {
        $f = $data['data']['features'];
        $key = $f['key'] ?? null;
        $camelot = $key && isset($camelotMap[$key]) ? $camelotMap[$key] : null;
        return ['bpm' => round($f['bpm'], 1), 'key' => $key, 'camelot' => $camelot, 'source' => 'melodata'];
    }
    return null;
}

$melodata_key = 'melo_sk_bb4deca8bae45503cc05c69b6b262270d7644595f6d7377565a952a0ea1ce604';
$results = [];
$cache_updated = false;
$missing_updated = false;

foreach ($spotify_ids as $spotify_id) {
    // PRIORITÉ 1 : BPM manuel
    if (isset($manual[$spotify_id])) {
        $results[$spotify_id] = array_merge($manual[$spotify_id], ['source' => 'manual']);
        continue;
    }

    // PRIORITÉ 2 : Cache Songstats
    if (isset($cache[$spotify_id]) && !empty($cache[$spotify_id]['bpm'])) {
        $results[$spotify_id] = $cache[$spotify_id];
        continue;
    }

    // PRIORITÉ 3 : Appel Songstats
    $entry = getSongstats($spotify_id, $camelotMap);
    if ($entry) {
        $results[$spotify_id] = $entry;
        $cache[$spotify_id] = $entry;
        $cache_updated = true;
        usleep(50000);
        continue;
    }

    // PRIORITÉ 4 : MeloData via ISRC (si on l'a depuis Songstats)
    $isrc = $cache[$spotify_id]['isrc'] ?? null;
    if ($isrc) {
        $melo = getMelodata($isrc, $melodata_key, $camelotMap);
        if ($melo) {
            $melo['isrc'] = $isrc;
            $results[$spotify_id] = $melo;
            $cache[$spotify_id] = $melo;
            $cache_updated = true;
            continue;
        }
    }

    // PRIORITÉ 5 : Non trouvé → logger dans missing
    $info = $track_info[$spotify_id] ?? [];
    $missing[$spotify_id] = [
        'title'  => $info['title'] ?? 'Inconnu',
        'artist' => $info['artist'] ?? '',
        'logged' => date('Y-m-d H:i')
    ];
    $missing_updated = true;
    $results[$spotify_id] = ['bpm' => null, 'key' => null, 'camelot' => null, 'source' => 'missing'];
}

if ($cache_updated)   file_put_contents($cache_file,   json_encode($cache,   JSON_PRETTY_PRINT));
if ($missing_updated) file_put_contents($missing_file, json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true, 'results' => $results]);
