<?php
echo "__DIR__ = " . __DIR__ . "\n";
echo "bpm_missing.json path = " . __DIR__ . '/bpm_missing.json' . "\n";
echo "exists = " . (file_exists(__DIR__ . '/bpm_missing.json') ? 'YES' : 'NO') . "\n";

// Lister les fichiers JSON dans le dossier
$files = glob(__DIR__ . '/*.json');
echo "Fichiers JSON trouvés:\n";
foreach ($files as $f) echo "  " . $f . "\n";
