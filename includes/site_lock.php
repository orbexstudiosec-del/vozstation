<?php
/**
 * Si el sitio está bloqueado, reemplaza toda la salida por un aviso fijo y
 * termina la ejecución. No afecta nunca al panel admin (ese vive aparte, en
 * /admin/, y no incluye este archivo) - así siempre se puede entrar a
 * desactivar el bloqueo.
 */
if (get_setting('site_locked') === '1') {
    $lockMessage = get_setting('site_locked_message', 'Sitio deshabilitado por falta de pago.');
    header('HTTP/1.1 503 Service Unavailable');
    header('Retry-After: 3600');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Sitio no disponible</title>
<style>
  * { box-sizing: border-box; }
  html, body {
    margin: 0;
    height: 100%;
    overflow: hidden;
    background: #08090c;
    color: #f1f1f1;
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
  }
  .lock-box { max-width: 480px; padding: 32px; }
  .lock-icon { font-size: 3rem; margin-bottom: 16px; }
  h1 { font-size: 1.4rem; font-weight: 800; margin: 0 0 12px; }
  p { color: #9aa0ac; margin: 0; }
</style>
</head>
<body>
  <div class="lock-box">
    <div class="lock-icon">&#9888;&#65039;</div>
    <h1>Sitio no disponible</h1>
    <p><?= e($lockMessage) ?></p>
  </div>
</body>
</html>
    <?php
    exit;
}
