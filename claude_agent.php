<?php
/**
 * ANWA DJ — Claude Agent
 * Génère une sélection de titres pour un genre donné via Claude API + web search
 * Retourne permanents + rotation séparés, prêts pour generatePlaylist()
 *
 * POST params:
 *   genre         : 'bachata' | 'salsa' | 'kizomba' | 'konpa'
 *   count         : nombre de titres souhaités (défaut: 20)
 *   spotify_token : token Spotify pour la recherche d'URIs
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

set_time_limit(120);

// ── Config ────────────────────────────────────────────────────────────────────
$CLAUDE_API_KEY  = $_SERVER['CLAUDE_API_KEY'] ?? getenv('CLAUDE_API_KEY') ?? '';
$CLAUDE_MODEL    = 'claude-sonnet-4-20250514';
$DATA_DIR        = __DIR__ . '/data';
$HISTORY_FILE    = $DATA_DIR . '/agent_history.json';
$PERMANENTS_FILE = $DATA_DIR . '/agent_permanents.json';

if (!is_dir($DATA_DIR)) mkdir($DATA_DIR, 0755, true);

// ── Input ─────────────────────────────────────────────────────────────────────
$body          = json_decode(file_get_contents('php://input'), true);
$genre         = strtolower(trim($body['genre'] ?? 'bachata'));
$count         = min(intval($body['count'] ?? 20), 40);
$spotify_token = $body['spotify_token'] ?? '';

if (!in_array($genre, ['bachata', 'salsa', 'kizomba', 'konpa'])) {
    echo json_encode(['error' => 'Genre invalide']); exit;
}

// ── Historique & Permanents ───────────────────────────────────────────────────
$history    = file_exists($HISTORY_FILE)    ? json_decode(file_get_contents($HISTORY_FILE), true)    : [];
$permanents = file_exists($PERMANENTS_FILE) ? json_decode(file_get_contents($PERMANENTS_FILE), true) : [];

// Titres joués dans les 3 dernières semaines (hors permanents)
$three_weeks_ago = strtotime('-3 weeks');
$recent_played   = [];
foreach ($history[$genre] ?? [] as $entry) {
    if ($entry['played_at'] > $three_weeks_ago) {
        $recent_played[] = strtolower($entry['title'] . ' ' . $entry['artists']);
    }
}

// Permanents actuels pour ce genre
$current_permanents = $permanents[$genre] ?? [];

// ── Graphes d'artistes par genre ──────────────────────────────────────────────
$ARTIST_GRAPHS = [
    'bachata' => [
        'original' => ['DJ Husky', 'Dimelo Cupido', 'Montelier', 'Charles Luis', 'SHAMA', 'Jensen', 'sP Polanco', 'Pinto Picasso', 'Akai Rojas', 'Jean & Alex', 'Dani J'],
        'remixer'  => ['DJ Tronky', 'DJ Sermaan', 'DJ Pablo G', 'DJ Dave Aguilar'],
        'classic'  => ['Romeo Santos', 'Aventura', 'Prince Royce', 'Manuel Turizo'],
        'sources'  => ['salsa.it bachata charts', 'bachatasociety.com latest songs', 'ballobachata.it classifica', 'Spotify playlist Bachata Hits 2026'],
    ],
    'salsa' => [
        'original' => ['Marc Anthony', 'Victor Manuelle', 'Gilberto Santa Rosa', 'La India', 'Cali Flow Latino'],
        'remixer'  => ['DJ Luian', 'DJ Nico'],
        'classic'  => ['El Gran Combo', 'Héctor Lavoe', 'Celia Cruz', 'Willie Colón'],
        'sources'  => ['Billboard Tropical Airplay salsa 2026', 'salsa.it classifica salsa', 'Spotify playlist Salsa Hits'],
    ],
    'kizomba' => [
        'original' => ['Kaysha', 'Ghetto Flow', 'Dji Tafinha', 'Anselmo Ralph', 'Landrick', 'Nelson Freitas', 'Mr. Bow'],
        'remixer'  => ['Ricky Rich', 'DJ Kyan'],
        'classic'  => ['Cabo Snoop', 'Eduardo Paim', 'Sara Tavares'],
        'sources'  => ['YouTube kizomba urban kiz nouvelles sorties 2026', 'SoundCloud kizomba trending 2026'],
    ],
    'konpa' => [
        'original' => ['BélO', 'Harmonik', 'Nu Look', 'Djakout #1', 'T-Vice'],
        'remixer'  => ['DJ Gracia'],
        'classic'  => ['Tabou Combo', 'Carimi', 'Sweet Micky'],
        'sources'  => ['YouTube konpa kompa nouvelles sorties 2026', 'Kompa Magazine nouveautés', 'Radio Mega Haiti playlist'],
    ],
];

$graph   = $ARTIST_GRAPHS[$genre];
$today   = date('d/m/Y');
$week_no = date('W');

// ── Prompt système Claude ─────────────────────────────────────────────────────
$system_prompt = <<<PROMPT
Tu es un agent de veille musicale expert en danses sociales latines (SBK : Salsa, Bachata, Kizomba, Konpa).
Ta mission : trouver les meilleurs titres {$genre} pour une soirée de danse sociale en France.

STRATÉGIE DE SÉLECTION (à respecter obligatoirement) :
1. Permanents : les titres phares ci-dessous, présents CHAQUE semaine
2. Rotation — Originaux : dernières sorties des producteurs du réseau
3. Rotation — Remixes : derniers remixes des DJs remixeurs
4. Rotation — Découverte : titres hors réseau connu, via sources spécialisées
5. Rotation — Classiques : 1-2 titres incontournables du genre

CONTRAINTES :
- Maximum 2 titres du même artiste sur l'ensemble permanents + rotation
- Exclure les titres joués récemment (liste ci-dessous) de la rotation
- Au moins 20% de titres hors réseau connu dans la rotation
- Titres adaptés à la danse sociale (pas trop lents, pas trop expérimentaux)
- Un titre dans "permanents" NE DOIT PAS apparaître dans "rotation" et vice-versa
- Chaque titre est unique dans l'ensemble permanents + rotation

Retourne UNIQUEMENT un JSON valide, sans markdown, sans backticks, sans commentaires :
{
  "permanents": [
    {
      "title": "titre exact",
      "artists": "artiste(s) exact(s)",
      "year": 2026,
      "category": "permanent",
      "search_query": "requête Spotify pour trouver ce titre"
    }
  ],
  "rotation": [
    {
      "title": "titre exact",
      "artists": "artiste(s) exact(s)",
      "year": 2026,
      "category": "original|remix|discovery|classic",
      "search_query": "requête Spotify pour trouver ce titre"
    }
  ],
  "promoted_to_permanent": ["titre1 - artiste1"],
  "retired_permanents": ["titre2 - artiste2"]
}
PROMPT;

// ── Message utilisateur ───────────────────────────────────────────────────────
$permanents_str    = !empty($current_permanents) ? implode(', ', array_column($current_permanents, 'title')) : 'Aucun pour l\'instant';
$recent_played_str = !empty($recent_played)      ? implode(', ', array_slice($recent_played, 0, 20))         : 'Aucun';
$originals_str     = implode(', ', $graph['original']);
$remixers_str      = implode(', ', $graph['remixer']);
$classics_str      = implode(', ', $graph['classic']);
$sources_str       = implode("\n- ", $graph['sources']);

$user_message = <<<MSG
Date : {$today} (semaine {$week_no})
Genre cible : {$genre}
Nombre de titres souhaités : {$count}

PERMANENTS ACTUELS (à inclure dans "permanents") :
{$permanents_str}

TITRES À EXCLURE DE LA ROTATION (joués récemment) :
{$recent_played_str}

RÉSEAU D'ARTISTES :
- Producteurs originaux : {$originals_str}
- Remixeurs : {$remixers_str}
- Classiques : {$classics_str}

SOURCES À CONSULTER :
- {$sources_str}

Recherche les dernières sorties et tendances, puis compose une sélection de {$count} titres {$genre} diversifiée.
MSG;

// ── Appel API Claude avec web search ─────────────────────────────────────────
function callClaude($api_key, $model, $system, $message) {
    $payload = [
        'model'      => $model,
        'max_tokens' => 2000,
        'system'     => $system,
        'tools'      => [['type' => 'web_search_20250305', 'name' => 'web_search']],
        'messages'   => [['role' => 'user', 'content' => $message]],
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
        ],
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        return ['_debug' => true, 'http_code' => $code, 'response' => $response, 'key_present' => !empty($api_key)];
    }
    return json_decode($response, true);
}

// ── Extraction du JSON dans la réponse Claude ─────────────────────────────────
function extractJson($content_blocks) {
    $text = '';
    foreach ($content_blocks as $block) {
        if ($block['type'] === 'text') $text .= $block['text'];
    }
    $text = preg_replace('/```json|```/i', '', $text);
    $text = trim($text);
    if (preg_match('/\{.*\}/s', $text, $m)) {
        return json_decode($m[0], true);
    }
    return null;
}

// ── Déduplication d'une liste de tracks ──────────────────────────────────────
function deduplicateTracks($tracks, &$seen) {
    return array_values(array_filter($tracks ?? [], function($t) use (&$seen) {
        $key = strtolower(trim($t['title'] ?? ''));
        if (!$key || isset($seen[$key])) return false;
        $seen[$key] = true;
        return true;
    }));
}

// ── Recherche URI Spotify ─────────────────────────────────────────────────────
function searchSpotifyUri($query, $token) {
    if (!$token) return null;
    $url = 'https://api.spotify.com/v1/search?' . http_build_query([
        'q' => $query, 'type' => 'track', 'limit' => 1, 'market' => 'FR',
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data  = json_decode($response, true);
    $items = $data['tracks']['items'] ?? [];
    if (empty($items)) return null;
    return [
        'uri'    => $items[0]['uri'],
        'name'   => $items[0]['name'],
        'artist' => $items[0]['artists'][0]['name'] ?? '',
        'year'   => intval(substr($items[0]['album']['release_date'] ?? '0', 0, 4)),
    ];
}

function resolveGenreLabel($genre) {
    if ($genre === 'konpa') return 'KO';
    return strtoupper(substr($genre, 0, 1));
}

// ── Exécution ─────────────────────────────────────────────────────────────────
$claude_response = callClaude($CLAUDE_API_KEY, $CLAUDE_MODEL, $system_prompt, $user_message);

if (!$claude_response || empty($claude_response['content'])) {
    echo json_encode(['error' => 'Erreur API Claude', 'detail' => $claude_response, 'key_check' => !empty($CLAUDE_API_KEY)]); exit;
}

$parsed = extractJson($claude_response['content']);

if (!$parsed || (empty($parsed['permanents']) && empty($parsed['rotation']))) {
    echo json_encode(['error' => 'Impossible de parser la réponse Claude']); exit;
}

// ── Déduplication PHP (filet de sécurité) ────────────────────────────────────
$seen = [];
$parsed['permanents'] = deduplicateTracks($parsed['permanents'] ?? [], $seen);
$parsed['rotation']   = deduplicateTracks($parsed['rotation']   ?? [], $seen);

// ── Résolution des URIs Spotify ───────────────────────────────────────────────
$genre_label    = resolveGenreLabel($genre);
$perm_results   = [];
$rot_results    = [];

foreach ($parsed['permanents'] as $track) {
    $spotify = searchSpotifyUri($track['search_query'] ?? ($track['title'] . ' ' . $track['artists']), $spotify_token);
    if (!$spotify) continue;
    $perm_results[] = [
        'uri'      => $spotify['uri'],
        'name'     => $spotify['name'],
        'artist'   => $spotify['artist'],
        'year'     => $spotify['year'],
        'category' => 'permanent',
        'genre'    => $genre_label,
    ];
}

foreach ($parsed['rotation'] as $track) {
    $spotify = searchSpotifyUri($track['search_query'] ?? ($track['title'] . ' ' . $track['artists']), $spotify_token);
    if (!$spotify) continue;
    $rot_results[] = [
        'uri'      => $spotify['uri'],
        'name'     => $spotify['name'],
        'artist'   => $spotify['artist'],
        'year'     => $spotify['year'],
        'category' => $track['category'] ?? 'discovery',
        'genre'    => $genre_label,
    ];
}

// Fusionner pour generatePlaylist() : permanents d'abord, puis rotation
$all_tracks = array_merge($perm_results, $rot_results);

// ── Mise à jour historique (rotation uniquement) ──────────────────────────────
if (!isset($history[$genre])) $history[$genre] = [];
$now = time();
foreach ($rot_results as $r) {
    $history[$genre][] = [
        'title'     => $r['name'],
        'artists'   => $r['artist'],
        'uri'       => $r['uri'],
        'played_at' => $now,
    ];
}
$three_months_ago = strtotime('-3 months');
$history[$genre]  = array_values(array_filter($history[$genre], fn($e) => $e['played_at'] > $three_months_ago));
file_put_contents($HISTORY_FILE, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ── Mise à jour permanents ────────────────────────────────────────────────────
foreach ($parsed['promoted_to_permanent'] ?? [] as $np) {
    $parts = explode(' - ', $np, 2);
    $permanents[$genre][] = ['title' => $parts[0] ?? $np, 'artists' => $parts[1] ?? '', 'added_at' => date('Y-m-d')];
}
foreach ($parsed['retired_permanents'] ?? [] as $rp) {
    $permanents[$genre] = array_filter($permanents[$genre], fn($p) => strtolower($p['title']) !== strtolower(explode(' - ', $rp)[0]));
}
$permanents[$genre] = array_values($permanents[$genre] ?? []);
file_put_contents($PERMANENTS_FILE, json_encode($permanents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ── Réponse ───────────────────────────────────────────────────────────────────
echo json_encode([
    'success'    => true,
    'genre'      => $genre,
    'count'      => count($all_tracks),
    'permanents' => $perm_results,
    'rotation'   => $rot_results,
    'tracks'     => $all_tracks, // Liste fusionnée pour generatePlaylist()
    'stats'      => [
        'permanent'  => count($perm_results),
        'original'   => count(array_filter($rot_results, fn($r) => $r['category'] === 'original')),
        'remix'      => count(array_filter($rot_results, fn($r) => $r['category'] === 'remix')),
        'discovery'  => count(array_filter($rot_results, fn($r) => $r['category'] === 'discovery')),
        'classic'    => count(array_filter($rot_results, fn($r) => $r['category'] === 'classic')),
    ],
]);
