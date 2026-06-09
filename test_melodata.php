<?php
$api_key = 'melo_sk_bb4deca8bae45503cc05c69b6b262270d7644595f6d7377565a952a0ea1ce604';
$headers = ['Authorization: Bearer ' . $api_key, 'Content-Type: application/json'];

$tests = [
    'Vivir Mi Vida ISRC'  => 'https://melodata.voltenworks.com/api/v1/tracks/USRC11200609/features',
    'Loco ISRC 1'         => 'https://melodata.voltenworks.com/api/v1/tracks/COLA02300259/features',
    'Loco ISRC 2'         => 'https://melodata.voltenworks.com/api/v1/tracks/COLA02300260/features',
];

foreach ($tests as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label — Code: $code\n";
    $data = json_decode($response, true);
    if ($code === 200 && isset($data['data']['features'])) {
        $f = $data['data']['features'];
        echo "  ✅ BPM: " . $f['bpm'] . " | Key: " . $f['key'] . " | Energy: " . $f['energy'] . "\n";
    } elseif ($code === 202) {
        echo "  ⏳ Encore en analyse...\n";
    } else {
        echo "  " . substr($response, 0, 150) . "\n";
    }
    echo "---\n";
}
