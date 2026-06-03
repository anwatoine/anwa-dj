<?php
// MusicBrainz - open source, gratuit, pas de clé API
$headers = ['User-Agent: ANWADanceApp/1.0 (contact@anwadance.com)'];

$tests = [
    'Search Loco DJ Husky' => 'https://musicbrainz.org/ws/2/recording/?query=recording:Loco+AND+artist:DJ+Husky&fmt=json&limit=5',
    'Search Loco Montelier' => 'https://musicbrainz.org/ws/2/recording/?query=recording:Loco+AND+artist:Montelier&fmt=json&limit=5',
    'Spotify ID lookup'    => 'https://musicbrainz.org/ws/2/recording/?query=spotify:1EMci9LycCKIO2X58jZPJ6&fmt=json&limit=3',
    'URL Spotify'          => 'https://musicbrainz.org/ws/2/url?resource=https://open.spotify.com/track/1EMci9LycCKIO2X58jZPJ6&inc=recording-rels&fmt=json',
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
        // Chercher les ISRCs dans les recordings
        if (isset($data['recordings'])) {
            foreach (array_slice($data['recordings'], 0, 3) as $rec) {
                echo "  → " . $rec['title'] . " | " . ($rec['artist-credit'][0]['name'] ?? '') . "\n";
                if (!empty($rec['isrcs'])) echo "    ISRC: " . implode(', ', $rec['isrcs']) . "\n";
            }
        } elseif (isset($data['relations'])) {
            foreach ($data['relations'] as $rel) {
                echo "  → " . json_encode($rel) . "\n";
            }
        } else {
            echo substr($response, 0, 300) . "\n";
        }
    }
    echo "---\n";
}
