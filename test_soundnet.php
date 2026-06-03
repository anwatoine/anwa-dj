<?php
$api_key = '73055c779emsh071a5199f63ddb8p1eca73jsn0d16bc7c9974';
$headers = [
    'Content-Type: application/json',
    'x-rapidapi-host: track-analysis.p.rapidapi.com',
    'x-rapidapi-key: ' . $api_key,
];

$tests = [
    'Loco seul'           => 'https://track-analysis.p.rapidapi.com/pktx/analysis?song=Loco',
    'Loco + Montelier'    => 'https://track-analysis.p.rapidapi.com/pktx/analysis?song=Loco&artist=Montelier',
    'Loco + Husky Montelier' => 'https://track-analysis.p.rapidapi.com/pktx/analysis?song=Loco&artist=Dj+Husky+Montelier',
    'Endpoint song list'  => 'https://track-analysis.p.rapidapi.com/pktx/songs?song=Loco&artist=DJ+Husky',
    'Endpoint search'     => 'https://track-analysis.p.rapidapi.com/pktx/search?q=Loco+DJ+Husky',
];

foreach ($tests as $label => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "$label — Code: $code\n";
    if ($code === 200) {
        echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo substr($response, 0, 200) . "\n";
    }
    echo "---\n";
}
