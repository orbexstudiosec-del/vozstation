<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$file = basename($_GET['file'] ?? '');
$dir = realpath(__DIR__ . '/../assets/uploads/tickets');
$path = $dir . '/' . $file;

if ($file === '' || !$dir || !is_file($path) || dirname(realpath($path)) !== $dir) {
    http_response_code(404);
    exit('No encontrado.');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'pdf' => 'application/pdf',
];

header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
