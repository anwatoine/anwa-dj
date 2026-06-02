<?php
$songstats_id = '09b3tl47';
$url = 'https://songstats.com/api/tracks/' . $songstats_id;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Referer: https://songstats.com/',
]);
curl_setopt($ch, CURLOPT_ENCODING, '');
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code: $code | Length: " . strlen($response) . "\n\n";

// Chercher BPM/tempo
if (preg_match('/"tempo"[:\s]+([0-9.]+)/i', $response, $m)) echo "Tempo: " . $m[1] . "\n";
if (preg_match('/"bpm"[:\s]+([0-9.]+)/i', $response, $m)) echo "BPM: " . $m[1] . "\n";
if (preg_match('/"key"[:\s]+"([^"]+)"/i', $response, $m)) echo "Key: " . $m[1] . "\n";

// Afficher le début
echo "\n--- Début réponse ---\n";
echo substr($response, 0, 1000);

// Si JSON, parser
$data = json_decode($response, true);
if ($data) {
    echo "\n--- JSON parsé ---\n";
    echo json_encode(array_slice($data, 0, 5), JSON_PRETTY_PRINT);
}
