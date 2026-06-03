<?php
$spotify_id = '1EMci9LycCKIO2X58jZPJ6';
$url = 'https://tunebat.com/Info/Loco-DJ-Husky/' . $spotify_id;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html', 'Referer: https://tunebat.com/']);
curl_setopt($ch, CURLOPT_ENCODING, '');
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code: $code | Length: " . strlen($response) . "\n\n";
// Afficher tout le contenu pour voir ce qu'il y a
echo $response;
