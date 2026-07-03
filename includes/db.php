<?php
require_once __DIR__ . '/config.php';

// includes/config.local.php es opcional y solo debe existir en tu máquina de
// desarrollo. Si está presente, se usa SQLite en vez de MySQL para poder
// correr el sitio sin instalar un servidor MySQL. No debe subirse a cPanel.
$localConfigFile = __DIR__ . '/config.local.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        if (defined('LOCAL_SQLITE_PATH')) {
            $isNew = !file_exists(LOCAL_SQLITE_PATH);
            $pdo = new PDO('sqlite:' . LOCAL_SQLITE_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            if ($isNew) {
                $pdo->exec(file_get_contents(__DIR__ . '/../database/schema_sqlite.sql'));
            }
            return $pdo;
        }

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos. Verifica includes/config.php');
        }
    }

    return $pdo;
}
