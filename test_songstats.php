<?php
$spotify_id = '1EMci9LycCKIO2X58jZPJ6'; // Loco - DJ Husky

// Étape 1 : Résoudre via songstats.com/t/
$ch = curl_init('https://songstats.com/t/' . $spotify_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_exec($ch);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "Final URL: $finalUrl\n";

if (preg_match('/songstats\.com\/track\/([a-z0-9]+)\//i', $finalUrl, $m)) {
    $songstats_id = $m[1];
    echo "Songstats ID: $songstats_id\n\n";

    // Étape 2 : Récupérer BPM
    $ch2 = curl_init('https://data.songstats.com/api/v1/analytics_track/' . $songstats_id . '/top?source=overview');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Origin: https://songstats.com', 'Referer: https://songstats.com/']);
    curl_setopt($ch2, CURLOPT_ENCODING, '');
    $data = json_decode(curl_exec($ch2), true);
    curl_close($ch2);

    if (isset($data['overviewInfo']['audioFeatureData']['summaryItems'])) {
        echo "✅ BPM trouvé !\n";
        foreach ($data['overviewInfo']['audioFeatureData']['summaryItems'] as $item) {
            echo $item['name'] . ': ' . $item['displayValue'] . "\n";
        }
    } else {
        echo "❌ Pas de BPM dans la réponse\n";
        echo "Réponse: " . substr(json_encode($data), 0, 300) . "\n";
    }
} else {
    echo "❌ Songstats ID non trouvé — titre probablement pas indexé\n";
    echo "URL finale: $finalUrl\n";
}
