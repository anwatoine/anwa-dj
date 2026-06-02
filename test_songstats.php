<?php
$songstats_id = '09b3tl47';
$url = 'https://songstats.com/api/tracks/' . $songstats_id;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/html, */*',
    'Referer: https://songstats.com/',
    'Accept-Encoding: identity', // Pas de compression
]);
// Ne pas décoder automatiquement
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "Code: $code\n";
echo "Content-Type: $content_type\n";
echo "Length: " . strlen($response) . "\n\n";

// Sauvegarder dans un fichier pour inspection
file_put_contents(__DIR__ . '/songstats_response.txt', $response);
echo "Fichier sauvegardé : songstats_response.txt\n\n";

// Afficher en hexa les 100 premiers octets
echo "Premiers octets (hex):\n";
echo bin2hex(substr($response, 0, 50)) . "\n\n";

// Afficher brut
echo "Début brut:\n";
echo substr($response, 0, 500);
