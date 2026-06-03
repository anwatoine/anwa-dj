<?php
$headers = ['User-Agent: ANWADanceApp/1.0 (contact@anwadance.com)'];

$tests = [
    'URL Spotify track' => 'https://musicbrainz.org/ws/2/url?resource=https://open.spotify.com/track/1EMci9LycCKIO2X58jZPJ6&fmt=json&inc=recording-rels',
    'Search Husky Montelier' => 'https://musicbrainz.org/ws/2/recording/?query=artist:Husky+AND+artist:Montelier&fmt=json&limit=5',
    'Search Loco 2023' => 'https://musicbrainz.org/ws/2/recording/?query=recording:Loco+AND+date:2023&fmt=json&limit=10',
    'ISRC lookup Colombia' => 'https://musicbrainz.org/ws/2/isrc/COLA02300259?fmt=json&inc=recordings',
];

foreach ($tests as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label — Code: $code\n";
    $data = json_decode($response, true);
    if ($code === 200 && $data) {
        if (isset($data['recordings'])) {
            foreach (array_slice($data['recordings'], 0, 5) as $rec) {
                echo "  → " . $rec['title'] . " | " . ($rec['artist-credit'][0]['name'] ?? '') . " | " . ($rec['first-release-date'] ?? '');
                if (!empty($rec['isrcs'])) echo " | ISRC: " . implode(', ', $rec['isrcs']);
                echo "\n";
            }
        } elseif (isset($data['relations'])) {
            echo "  Relations: " . count($data['relations']) . "\n";
            foreach (array_slice($data['relations'], 0, 3) as $rel) {
                echo "  → " . json_encode($rel) . "\n";
            }
        } else {
            echo substr(json_encode($data), 0, 400) . "\n";
        }
    } else {
        echo substr($response, 0, 200) . "\n";
    }
    echo "---\n";
}
