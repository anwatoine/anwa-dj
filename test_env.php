<?php
echo json_encode([
    'getenv'  => getenv('CLAUDE_API_KEY'),
    'server'  => $_SERVER['CLAUDE_API_KEY'] ?? 'absent',
    'apache'  => apache_getenv('CLAUDE_API_KEY') ?? 'absent',
]);
