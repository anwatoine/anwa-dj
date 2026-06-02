<?php
$spotify_id = '3QHMxEOAGD51PDlbFPHLyJ';
$url = 'https://tunebat.com/api/tracks/' . $spotify_id;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, */*',
    'Accept-Language: fr-FR,fr;q=0.9',
    'Referer: https://tunebat.com/',
]);
curl_setopt($ch, CURLOPT_ENCODING, '');
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code: $code\n\n";
echo $response;
