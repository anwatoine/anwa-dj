<?php
$spotify_id = '1EMci9LycCKIO2X58jZPJ6';

// Tentative via ScrapingBee (gratuit 1000 req/mois)
$scrapers = [
    'scrapingbee' => 'https://app.scrapingbee.com/api/v1/?api_key=FREE&url=' . urlencode('https://tunebat.com/Info/Loco-DJ-Husky/' . $spotify_id) . '&render_js=false',
    'scrape.do'   => 'https://api.scrape.do?token=FREE&url=' . urlencode('https://tunebat.com/Info/Loco-DJ-Husky/' . $spotify_id),
    'allorigins'  => 'https://api.allorigins.win/raw?url=' . urlencode('https://tunebat.com/Info/Loco-DJ-Husky/' . $spotify_id),
    'corsproxy'   => 'https://corsproxy.io/?' . urlencode('https://tunebat.com/Info/Loco-DJ-Husky/' . $spotify_id),
];

foreach ($scrapers as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label — Code: $code | Length: " . strlen($response) . "\n";
    if ($code === 200 && strlen($response) > 1000) {
        if (preg_match('/10A/', $response)) echo "  ✅ Camelot 10A trouvé !\n";
        if (preg_match('/"bpm"[:\s]+120/', $response)) echo "  ✅ BPM 120 trouvé !\n";
        if (preg_match('/B Minor/', $response)) echo "  ✅ Key B Minor trouvé !\n";
    }
    echo "---\n";
}
