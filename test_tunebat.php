<?php
// Test : récupérer BPM depuis Tunebat via l'ID Spotify
$spotify_id = '3QHMxEOAGD51PDlbFPHLyJ'; // Vivir Mi Vida - Marc Anthony
$url = 'https://tunebat.com/Info/track/' . $spotify_id;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
    'Accept-Encoding: gzip, deflate, br',
    'Cache-Control: no-cache',
    'Referer: https://tunebat.com/',
]);
curl_setopt($ch, CURLOPT_ENCODING, '');
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Content length: " . strlen($html) . "\n\n";

// Chercher le BPM dans le HTML
if (preg_match('/"tempo"[:\s]+([0-9.]+)/i', $html, $m)) {
    echo "BPM trouvé (tempo): " . $m[1] . "\n";
} elseif (preg_match('/BPM["\s:]+([0-9]+)/i', $html, $m)) {
    echo "BPM trouvé: " . $m[1] . "\n";
} elseif (preg_match('/"bpm"[:\s]+([0-9.]+)/i', $html, $m)) {
    echo "BPM trouvé (bpm): " . $m[1] . "\n";
} else {
    echo "BPM non trouvé\n";
}

// Chercher du JSON dans la page
if (preg_match('/<script[^>]*type="application\/json"[^>]*>(.*?)<\/script>/s', $html, $m)) {
    echo "\nJSON trouvé !\n";
    $data = json_decode($m[1], true);
    echo substr(json_encode($data, JSON_PRETTY_PRINT), 0, 500);
} elseif (preg_match('/__NEXT_DATA__[^>]*>(.*?)<\/script>/s', $html, $m)) {
    echo "\nNext.js data trouvé !\n";
    echo substr($m[1], 0, 500);
} else {
    echo "\nPas de JSON structuré trouvé\n";
    echo "Premiers 300 chars du HTML:\n";
    echo substr($html, 0, 300);
}
