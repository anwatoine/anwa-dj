<?php
$idUnique = 'sb042k69';

function getBPM($spotify_id, $idUnique) {
    // Étape 1 : Résoudre l'ID Spotify via Tunebat/Songstats
    $url = 'https://songstats.com/t/' . $spotify_id;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html', 'Referer: https://tunebat.com/']);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    echo "Spotify ID: $spotify_id\n";
    echo "Code: $code | Final URL: $finalUrl\n";
    
    // Extraire le songstats_id depuis l'URL finale
    if (preg_match('/songstats\.com\/track\/([a-z0-9]+)\//i', $finalUrl, $m)) {
        $songstats_id = $m[1];
        echo "Songstats ID: $songstats_id\n";
        
        // Étape 2 : Récupérer le BPM
        $bpm_url = 'https://data.songstats.com/api/v1/analytics_track/' . $songstats_id . '/top?source=overview&idUnique=' . $idUnique;
        $ch2 = curl_init($bpm_url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Origin: https://songstats.com', 'Referer: https://songstats.com/']);
        curl_setopt($ch2, CURLOPT_ENCODING, '');
        $bpm_response = curl_exec($ch2);
        curl_close($ch2);
        
        $data = json_decode($bpm_response, true);
        if (isset($data['overviewInfo']['audioFeatureData']['summaryItems'])) {
            echo "✅ BPM trouvé !\n";
            foreach ($data['overviewInfo']['audioFeatureData']['summaryItems'] as $item) {
                echo "  " . $item['name'] . ': ' . $item['displayValue'] . "\n";
            }
        } else {
            echo "❌ BPM non trouvé\n";
        }
    } else {
        echo "❌ Songstats ID non trouvé dans: $finalUrl\n";
    }
    echo "---\n";
}

// Test avec plusieurs titres de nos genres
getBPM('3QHMxEOAGD51PDlbFPHLyJ', $idUnique); // Vivir Mi Vida - Marc Anthony
getBPM('7scFxt9VhL4FJwuPSfRlfN', $idUnique); // Titre test Tunebat
getBPM('0DiWol3AO6WpXZgp0goxAV', $idUnique); // One More Time - Daft Punk
