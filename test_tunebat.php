<?php
$spotify_id = '1EMci9LycCKIO2X58jZPJ6'; // Loco - DJ Husky

$urls = [
    'page directe' => 'https://tunebat.com/Info/Loco-DJ-Husky/' . $spotify_id,
    'API interne'  => 'https://api.tunebat.com/api/tracks/' . $spotify_id,
    'API v2'       => 'https://api.tunebat.com/api/v2/tracks/' . $spotify_id,
    'data API'     => 'https://tunebat.com/api/tracks/data?id=' . $spotify_id,
];

$headers = [
    'Accept: application/json, text/html, */*',
    'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
    'Accept-Encoding: identity',
    'Cache-Control: no-cache',
    'Referer: https://tunebat.com/',
    'sec-ch-ua: "Microsoft Edge";v="148"',
    'sec-fetch-dest: document',
    'sec-fetch-mode: navigate',
];

foreach ($urls as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, 'identity');
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label — Code: $code | Length: " . strlen($response) . "\n";
    
    // Chercher BPM dans la réponse
    if ($code === 200 && strlen($response) > 100) {
        if (preg_match('/"bpm"[:\s]+([0-9.]+)/i', $response, $m)) echo "  BPM: " . $m[1] . "\n";
        if (preg_match('/"tempo"[:\s]+([0-9.]+)/i', $response, $m)) echo "  Tempo: " . $m[1] . "\n";
        if (preg_match('/"camelot"[:\s]+"([^"]+)"/i', $response, $m)) echo "  Camelot: " . $m[1] . "\n";
        if (preg_match('/10A/', $response)) echo "  ✅ 10A trouvé !\n";
        if (preg_match('/120/', $response)) echo "  ✅ 120 BPM trouvé !\n";
        echo "  Début: " . substr($response, 0, 200) . "\n";
    }
    echo "---\n";
}
