<?php
// Minimal health check without loading Composer or sessions
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo json_encode(['status' => 'ok', 'time' => gmdate('c')]);
exit;

