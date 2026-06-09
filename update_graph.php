<?php
/**
 * ANWA DJ — Update Artist Graph
 * Met à jour artist_graphs.json en combinant :
 *   1. GET /artists/{id}/albums?include_groups=appears_on  → artistes sur lesquels DJ Husky apparaît
 *   2. GET /artists/{id}/albums?include_groups=single,album → artistes qui apparaissent sur ses propres sorties
 *
 * Utilise le token Spotify stocké par l'extension Chrome (admin.php)
 * À lancer manuellement ou via cron une fois par mois
 *
 * GET  update_graph.php?genre=bachata  → met à jour le graphe bachata
 * GET  update_graph.php?genre=all      → met à jour tous les genres
 * GET  update_graph.php?preview=1      → preview sans sauvegarder
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

set_time_limit(120);

// ── Config ────────────────────────────────────────────────────────────────────
$DATA_DIR    = '/home/u343863374/dj_data';
$GRAPH_FILE  = $DATA_DIR . '/artist_graphs.json';
$TOKEN_FILE  = '/home/u343863374/domains/anwadance.com/public_html/dj/spotify_token.json';

if (!is_dir($DATA_DIR)) mkdir($DATA_DIR, 0755, true);

// ── Paramètres ────────────────────────────────────────────────────────────────
$genre   = $_GET['genre']   ?? 'bachata';
$preview = $_GET['preview'] ?? false;

// ── Token Spotify ─────────────────────────────────────────────────────────────
function getSpotifyToken($token_file) {
    if (!file_exists($token_file)) return null;
    $data = json_decode(file_get_contents($token_file), true);
    if (!$data || $data['expires_at'] < time()) return null;
    return $data['token'];
}

$token = getSpotifyToken($TOKEN_FILE);
if (!$token) {
    echo json_encode(['error' => 'Token Spotify expiré ou manquant — renouveler via l\'extension Chrome']);
    exit;
}

// ── Graphes initiaux (fallback si pas encore de JSON) ─────────────────────────
$DEFAULT_GRAPHS = [
    'bachata' => [
        'core' => [
            ['name' => 'DJ Husky',      'spotify_id' => '7KpJV35QbeZ1ZCn34bnypL', 'type' => 'original'],
            ['name' => 'Dimelo Cupido', 'spotify_id' => '5P8PGkHPGVULfxVnuGJvcp', 'type' => 'original'],
            ['name' => 'Montelier',     'spotify_id' => '56O28NX1Su8GSYhuNGupjI', 'type' => 'original'],
            ['name' => 'DJ Tronky',     'spotify_id' => '5jqxB07RfRMiMKXjFAJVd6', 'type' => 'remixer'],
            ['name' => 'DJ Sermaan',    'spotify_id' => '5r5FluMsCM3yFPBWiNBFTz', 'type' => 'remixer'],
        ],
        'network'  => [],
        'classic'  => ['Romeo Santos', 'Aventura', 'Prince Royce', 'Manuel Turizo'],
        'sources'  => ['salsa.it bachata charts', 'bachatasociety.com', 'ballobachata.it', 'Spotify playlist Bachata Hits 2026'],
        'updated_at' => null,
    ],
    'salsa' => [
        'core' => [
            ['name' => 'Marc Anthony',       'spotify_id' => '1KHf9KQGMoWL1DbWFCFiSV', 'type' => 'original'],
            ['name' => 'Victor Manuelle',    'spotify_id' => '70fLoRCvFkYIJcLq5TKZVL', 'type' => 'original'],
            ['name' => 'Gilberto Santa Rosa','spotify_id' => '4oUHIQIBe0LHzYfvXgIypg', 'type' => 'original'],
        ],
        'network'  => [],
        'classic'  => ['El Gran Combo', 'Héctor Lavoe', 'Celia Cruz', 'Willie Colón'],
        'sources'  => ['Billboard Tropical Airplay salsa 2026', 'salsa.it classifica salsa'],
        'updated_at' => null,
    ],
    'kizomba' => [
        'core' => [
            ['name' => 'Kaysha',      'spotify_id' => '1FxkLHZxBClgxDdgFnCHuG', 'type' => 'original'],
            ['name' => 'Ghetto Flow', 'spotify_id' => '0YnCiJMgCCTNMTVF3xJJeU', 'type' => 'original'],
            ['name' => 'Dji Tafinha', 'spotify_id' => '0oxG6n2TGKVE0SjcZf9LoF', 'type' => 'original'],
        ],
        'network'  => [],
        'classic'  => ['Cabo Snoop', 'Eduardo Paim', 'Sara Tavares'],
        'sources'  => ['YouTube kizomba urban kiz 2026', 'SoundCloud kizomba trending'],
        'updated_at' => null,
    ],
    'konpa' => [
        'core' => [
            ['name' => 'BélO',     'spotify_id' => '3AMut4SGMlwnXHiOALMBbp', 'type' => 'original'],
            ['name' => 'Harmonik', 'spotify_id' => '3A0iSUeaFfMTKRAdVMkdNh', 'type' => 'original'],
            ['name' => 'Nu Look',  'spotify_id' => '3dGe3GXiHOPTNsNv1tOmJH', 'type' => 'original'],
        ],
        'network'  => [],
        'classic'  => ['Tabou Combo', 'Carimi', 'Sweet Micky'],
        'sources'  => ['YouTube konpa 2026', 'Kompa Magazine', 'Radio Mega Haiti'],
        'updated_at' => null,
    ],
];

// Charger le graphe existant ou utiliser le défaut
$graphs = file_exists($GRAPH_FILE)
    ? json_decode(file_get_contents($GRAPH_FILE), true)
    : $DEFAULT_GRAPHS;

// ── Fonctions Spotify ─────────────────────────────────────────────────────────

// Appel API Spotify générique
function spotifyGet($url, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return null;
    return json_decode($response, true);
}

// Récupérer tous les collaborateurs d'un artiste (appears_on + single/album)
function getCollaborators($artist_id, $token) {
    $collaborators = [];

    // ── Appel 1 : appears_on ─────────────────────────────────────────────────
    $url  = 'https://api.spotify.com/v1/artists/' . $artist_id . '/albums?include_groups=appears_on&limit=50&market=FR';
    $data = spotifyGet($url, $token);
    if ($data && isset($data['items'])) {
        foreach ($data['items'] as $album) {
            foreach ($album['artists'] as $a) {
                if ($a['id'] === $artist_id) continue; // Exclure l'artiste lui-même
                if (!isset($collaborators[$a['id']])) {
                    $collaborators[$a['id']] = ['name' => $a['name'], 'count' => 0];
                }
                $collaborators[$a['id']]['count']++;
            }
        }
    }

    // ── Appel 2 : single + album ─────────────────────────────────────────────
    $url  = 'https://api.spotify.com/v1/artists/' . $artist_id . '/albums?include_groups=single,album&limit=50&market=FR';
    $data = spotifyGet($url, $token);
    if ($data && isset($data['items'])) {
        foreach ($data['items'] as $album) {
            foreach ($album['artists'] as $a) {
                if ($a['id'] === $artist_id) continue;
                if (!isset($collaborators[$a['id']])) {
                    $collaborators[$a['id']] = ['name' => $a['name'], 'count' => 0];
                }
                $collaborators[$a['id']]['count']++;
            }
        }
    }

    // Trier par fréquence de collaboration
    uasort($collaborators, fn($a, $b) => $b['count'] - $a['count']);

    return $collaborators;
}

// ── Mise à jour du graphe pour un genre ──────────────────────────────────────
function updateGenreGraph($genre, &$graphs, $token, $preview) {
    $graph    = $graphs[$genre] ?? [];
    $core     = $graph['core'] ?? [];
    $log      = [];
    $new_network = $graph['network'] ?? [];

    // IDs déjà connus (core + network) pour éviter les doublons
    $known_ids   = array_column($core, 'spotify_id');
    $known_names = array_map('strtolower', array_merge(
        array_column($core, 'name'),
        array_column($new_network, 'name')
    ));

    // Artistes à ignorer (labels, compilations, multi-artistes)
    $ignore_patterns = ['multi', 'various', 'varios', 'interprètes', 'artistes'];

    foreach ($core as $artist) {
        $id   = $artist['spotify_id'] ?? null;
        $name = $artist['name'];
        if (!$id) continue;

        $log[] = "🔍 Analyse de {$name}...";
        $collabs = getCollaborators($id, $token);

        foreach ($collabs as $collab_id => $collab) {
            $collab_name_lower = strtolower($collab['name']);

            // Ignorer si déjà connu
            if (in_array($collab_id, $known_ids)) continue;
            if (in_array($collab_name_lower, $known_names)) continue;

            // Ignorer les patterns parasites
            $skip = false;
            foreach ($ignore_patterns as $pattern) {
                if (strpos($collab_name_lower, $pattern) !== false) { $skip = true; break; }
            }
            if ($skip) continue;

            // Seuil minimum : au moins 1 collaboration
            if ($collab['count'] < 1) continue;

            $log[]       = "  ✅ Nouveau : {$collab['name']} ({$collab['count']} collab(s) avec {$name})";
            $known_ids[] = $collab_id;
            $known_names[] = $collab_name_lower;

            $new_network[] = [
                'name'         => $collab['name'],
                'spotify_id'   => $collab_id,
                'type'         => 'network',
                'collabs'      => $collab['count'],
                'via'          => $name,
                'discovered_at'=> date('Y-m-d'),
            ];
        }
    }

    // Trier le network par nombre de collabs
    usort($new_network, fn($a, $b) => ($b['collabs'] ?? 0) - ($a['collabs'] ?? 0));

    if (!$preview) {
        $graphs[$genre]['network']    = $new_network;
        $graphs[$genre]['updated_at'] = date('Y-m-d H:i');
    }

    return [
        'genre'       => $genre,
        'core_count'  => count($core),
        'network_count'=> count($new_network),
        'network'     => $new_network,
        'log'         => $log,
    ];
}

// ── Exécution ─────────────────────────────────────────────────────────────────
$results = [];

$genres_to_update = $genre === 'all'
    ? ['bachata', 'salsa', 'kizomba', 'konpa']
    : [$genre];

foreach ($genres_to_update as $g) {
    if (!isset($graphs[$g])) {
        $results[$g] = ['error' => 'Genre inconnu'];
        continue;
    }
    $results[$g] = updateGenreGraph($g, $graphs, $token, $preview);
}

// Sauvegarder si pas en mode preview
if (!$preview) {
    file_put_contents($GRAPH_FILE, json_encode($graphs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

echo json_encode([
    'success'  => true,
    'preview'  => (bool)$preview,
    'saved_to' => $preview ? null : $GRAPH_FILE,
    'results'  => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
