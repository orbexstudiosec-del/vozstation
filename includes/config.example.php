<?php
// Configuración de conexión a la base de datos MySQL
// Copia este archivo como "config.php" y reemplaza estos valores con los datos
// que te da cPanel al crear la base de datos (cPanel > MySQL Databases).
// El nombre de usuario y de la base suelen llevar el prefijo de tu cuenta,
// ej: "usuario_vozstation"
//
// IMPORTANTE: config.php NO se sube a git (está en .gitignore) porque
// contiene credenciales reales.

define('DB_HOST', 'localhost');
define('DB_NAME', 'usuario_vozstation');
define('DB_USER', 'usuario_vozstation');
define('DB_PASS', 'TU_PASSWORD_AQUI');

// Zona horaria del sitio
date_default_timezone_set('America/Guayaquil');
