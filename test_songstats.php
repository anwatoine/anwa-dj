<?php
// Test de l'endpoint de recherche découvert
$url = 'https://data.songstats.com/api/v1/search/search_all?q=Vivir+Mi+Vida+Marc+Anthony&excludedModels=RadioStation&idUnique=sb042k69';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Origin: https://songstats.com',
    'Referer: https://songstats.com/',
]);
curl_setopt($ch, CURLOPT_ENCODING, '');
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code: $code | Length: " . strlen($response) . "\n\n";

if ($response[0] === '{' || $response[0] === '[') {
    $data = json_decode($response, true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo substr($response, 0, 500);
}
