<?php
// Test avec l'URL exacte découverte dans le Network
$songstats_id = '09b3tl47';
$entity_id = 'exb4oc75';
$url = 'https://data.songstats.com/api/v1/analytics_track/' . $songstats_id . '/top?source=overview&idUnique=' . $entity_id;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Origin: https://songstats.com',
    'Referer: https://songstats.com/track/' . $songstats_id,
]);
curl_setopt($ch, CURLOPT_ENCODING, '');
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code: $code\n";
echo "Length: " . strlen($response) . "\n\n";

$data = json_decode($response, true);
if ($data && isset($data['overviewInfo']['audioFeatureData']['summaryItems'])) {
    foreach ($data['overviewInfo']['audioFeatureData']['summaryItems'] as $item) {
        echo $item['name'] . ': ' . $item['displayValue'] . "\n";
    }
} else {
    echo "Réponse brute:\n" . substr($response, 0, 500);
}
