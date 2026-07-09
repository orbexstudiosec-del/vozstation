<?php
require_once __DIR__ . '/db.php';

$DAYS_ES = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo',
];

function get_all_settings(): array
{
    static $settings = null;

    if ($settings === null) {
        $pdo = get_db();
        $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $settings;
}

function get_setting(string $key, string $default = ''): string
{
    $settings = get_all_settings();
    return $settings[$key] ?? $default;
}

function save_setting(string $key, string $value): void
{
    $pdo = get_db();
    $exists = $pdo->prepare('SELECT 1 FROM settings WHERE setting_key = :key');
    $exists->execute(['key' => $key]);

    if ($exists->fetchColumn()) {
        $stmt = $pdo->prepare('UPDATE settings SET setting_value = :value WHERE setting_key = :key');
    } else {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)');
    }
    $stmt->execute(['key' => $key, 'value' => $value]);
}

function get_schedule(): array
{
    $pdo = get_db();
    $rows = $pdo->query(
        'SELECT * FROM schedule ORDER BY day_of_week ASC, sort_order ASC, start_time ASC'
    )->fetchAll();

    $grouped = [];
    foreach ($rows as $row) {
        $grouped[(int) $row['day_of_week']][] = $row;
    }

    return $grouped;
}

function get_slides(): array
{
    $pdo = get_db();
    return $pdo->query('SELECT * FROM slides ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function render_brand_wordmark(string $siteName): string
{
    if (stripos($siteName, 'voz') === 0) {
        $first = substr($siteName, 0, 3);
        $rest = substr($siteName, 3);
        return '<span class="brand-voz">' . e($first) . '</span><span class="brand-station">' . e($rest) . '.</span>';
    }

    return '<span class="brand-station">' . e($siteName) . '</span>';
}

function format_time(string $time): string
{
    return date('g:i A', strtotime($time));
}

/**
 * Valida y mueve un archivo subido. Devuelve ['path' => string|null, 'error' => string|null].
 * $uploadDir es relativo a la raíz del sitio (ej. 'assets/img/uploads/').
 */
function handle_file_upload(
    string $fieldName,
    string $prefix,
    array $allowedExts,
    string $uploadDir,
    int $maxBytes,
    string $typeLabel = 'archivo'
): array {
    if (empty($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => null];
    }

    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts, true)) {
        return ['path' => null, 'error' => 'Formato no permitido. Usa: ' . strtoupper(implode(', ', $allowedExts)) . '.'];
    }

    if ($_FILES[$fieldName]['size'] > $maxBytes) {
        return ['path' => null, 'error' => 'El ' . $typeLabel . ' no debe superar ' . round($maxBytes / 1024 / 1024) . 'MB.'];
    }

    $absoluteDir = __DIR__ . '/../' . $uploadDir;
    $filename = $prefix . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $absoluteDir . $filename)) {
        return ['path' => null, 'error' => 'No se pudo subir el ' . $typeLabel . '. Verifica permisos de ' . $uploadDir . '.'];
    }

    return ['path' => $uploadDir . $filename, 'error' => null];
}

function handle_image_upload(string $fieldName, string $prefix, int $maxBytes = 3 * 1024 * 1024): array
{
    return handle_file_upload(
        $fieldName,
        $prefix,
        ['jpg', 'jpeg', 'png', 'webp', 'svg'],
        'assets/img/uploads/',
        $maxBytes,
        'imagen'
    );
}

function generate_ticket_code(): string
{
    return 'VOZ-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}
