<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fields = [
        'site_name', 'tagline', 'description', 'about_text',
        'stream_url', 'stream_format', 'video_stream_url', 'ad_banner_text',
        'phone', 'whatsapp', 'email', 'address', 'map_coordinates',
        'facebook', 'instagram', 'twitter', 'youtube', 'tiktok', 'primary_color', 'secondary_color',
        'tickets_event_name', 'tickets_event_date', 'tickets_price', 'tickets_description',
    ];

    foreach ($fields as $field) {
        $value = trim($_POST[$field] ?? '');
        save_setting($field, $value);
    }

    save_setting('tickets_enabled', !empty($_POST['tickets_enabled']) ? '1' : '');

    // Manejo de subida de logo (opcional)
    $logoUpload = handle_image_upload('logo', 'logo', 2 * 1024 * 1024);
    if ($logoUpload['error']) {
        $error = $logoUpload['error'];
    } elseif ($logoUpload['path']) {
        save_setting('logo', $logoUpload['path']);
    }

    // Manejo de subida de imagen de fondo del hero (opcional)
    if (!$error) {
        $heroUpload = handle_image_upload('hero_image', 'hero', 4 * 1024 * 1024);
        if ($heroUpload['error']) {
            $error = $heroUpload['error'];
        } elseif ($heroUpload['path']) {
            save_setting('hero_image', $heroUpload['path']);
        }
    }

    if (!empty($_POST['remove_hero_image'])) {
        save_setting('hero_image', '');
    }

    // Manejo de subida de imagen/poster del evento de tickets (opcional)
    if (!$error) {
        $eventImageUpload = handle_image_upload('tickets_event_image', 'event', 4 * 1024 * 1024);
        if ($eventImageUpload['error']) {
            $error = $eventImageUpload['error'];
        } elseif ($eventImageUpload['path']) {
            save_setting('tickets_event_image', $eventImageUpload['path']);
        }
    }

    if (!empty($_POST['remove_event_image'])) {
        save_setting('tickets_event_image', '');
    }

    if (!$error) {
        header('Location: index.php?saved=1');
        exit;
    }
}

if (isset($_GET['saved'])) {
    $message = 'Configuración guardada correctamente.';
}

require_once __DIR__ . '/includes/header.php';

$settings = get_all_settings();
function v(string $key): string
{
    global $settings;
    return e($settings[$key] ?? '');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">Configuración general</h1>
</div>

<?php if ($message): ?>
  <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

  <div class="admin-card">
    <h2 class="h5 mb-3">Identidad de la radio</h2>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nombre de la radio</label>
        <input type="text" name="site_name" class="form-control" value="<?= v('site_name') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Slogan / Tagline</label>
        <input type="text" name="tagline" class="form-control" value="<?= v('tagline') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Descripción corta (aparece en el hero y buscadores)</label>
        <textarea name="description" class="form-control" rows="2"><?= v('description') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Texto "Nosotros"</label>
        <textarea name="about_text" class="form-control" rows="4"><?= v('about_text') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Logo</label>
        <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg">
        <?php if (!empty($settings['logo'])): ?>
          <img src="../<?= e($settings['logo']) ?>" class="logo-preview mt-2" alt="Logo actual">
        <?php endif; ?>
      </div>
      <div class="col-md-3">
        <label class="form-label">Color principal (rojo)</label>
        <input type="color" name="primary_color" class="form-control form-control-color color-swatch"
               value="<?= v('primary_color') ?: '#e50914' ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Color secundario (azul)</label>
        <input type="color" name="secondary_color" class="form-control form-control-color color-swatch"
               value="<?= v('secondary_color') ?: '#1e6fe8' ?>">
      </div>
    </div>
  </div>

  <div class="admin-card">
    <h2 class="h5 mb-3">Imagen de fondo del hero</h2>
    <p class="text-muted small">Foto que aparece de fondo en la portada principal (cabina, DJs, eventos). Se muestra con una capa oscura encima para que el texto se siga leyendo. Si no subes ninguna, se usa el degradado rojo/azul por defecto.</p>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Imagen (recomendado: horizontal, mínimo 1600px de ancho)</label>
        <input type="file" name="hero_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
      </div>
      <?php if (!empty($settings['hero_image'])): ?>
        <div class="col-md-6">
          <label class="form-label">Imagen actual</label>
          <div>
            <img src="../<?= e($settings['hero_image']) ?>" class="logo-preview mt-2" alt="Fondo del hero actual" style="max-height:100px;">
          </div>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="remove_hero_image" value="1" id="removeHeroImage">
            <label class="form-check-label small" for="removeHeroImage">Quitar imagen y volver al degradado</label>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-card">
    <h2 class="h5 mb-3">Transmisión en vivo</h2>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">URL del stream (Icecast / Shoutcast / mp3)</label>
        <input type="url" name="stream_url" class="form-control" value="<?= v('stream_url') ?>"
               placeholder="https://stream.tuservidor.com/radio" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Formato</label>
        <select name="stream_format" class="form-select">
          <?php foreach (['mp3', 'aac', 'ogg'] as $fmt): ?>
            <option value="<?= $fmt ?>" <?= ($settings['stream_format'] ?? '') === $fmt ? 'selected' : '' ?>>
              <?= strtoupper($fmt) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <hr>
        <label class="form-label">URL del video / TV en vivo (HLS .m3u8)</label>
        <input type="url" name="video_stream_url" class="form-control" value="<?= v('video_stream_url') ?>"
               placeholder="https://tuservidor.com/tv/stream.m3u8">
        <div class="form-text">Opcional. Si la dejas vacía, la sección de TV en vivo no se muestra en el sitio.</div>
      </div>
    </div>
  </div>

  <div class="admin-card">
    <h2 class="h5 mb-3">Banner "Anúnciate con nosotros"</h2>
    <label class="form-label">Texto del banner</label>
    <textarea name="ad_banner_text" class="form-control" rows="2"><?= v('ad_banner_text') ?></textarea>
    <div class="form-text">Aparece como una franja destacada en la portada. El botón del banner usa tu número de WhatsApp de la sección de Contacto.</div>
  </div>

  <div class="admin-card">
    <h2 class="h5 mb-3">Venta de entradas</h2>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="tickets_enabled" value="1" id="ticketsEnabled"
             <?= get_setting('tickets_enabled') === '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="ticketsEnabled">Venta de entradas activa</label>
      <div class="form-text">Si está desmarcado, la página de compra de entradas muestra "sin venta activa" y no aparece el aviso en el sitio.</div>
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nombre del evento</label>
        <input type="text" name="tickets_event_name" class="form-control" value="<?= v('tickets_event_name') ?>"
               placeholder="Ej: Gran Concierto VozStation">
      </div>
      <div class="col-md-6">
        <label class="form-label">Fecha / lugar (texto libre)</label>
        <input type="text" name="tickets_event_date" class="form-control" value="<?= v('tickets_event_date') ?>"
               placeholder="Ej: Sábado 15 de agosto, 8:00 PM - Coliseo">
      </div>
      <div class="col-md-4">
        <label class="form-label">Precio por entrada (USD)</label>
        <input type="number" step="0.01" min="0" name="tickets_price" class="form-control" value="<?= v('tickets_price') ?: '0' ?>">
        <div class="form-text">Pon 0 si la entrada es gratuita (igual se pide comprobante si aplica).</div>
      </div>
      <div class="col-md-8">
        <label class="form-label">Descripción del evento</label>
        <textarea name="tickets_description" class="form-control" rows="2"><?= v('tickets_description') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Imagen / póster del evento (opcional)</label>
        <input type="file" name="tickets_event_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">Recomendado: vertical, tipo póster (ej. 800×1000px).</div>
      </div>
      <?php if (!empty($settings['tickets_event_image'])): ?>
        <div class="col-md-6">
          <label class="form-label">Imagen actual</label>
          <div><img src="../<?= e($settings['tickets_event_image']) ?>" class="logo-preview mt-2" alt="Póster actual" style="max-height:100px;"></div>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="remove_event_image" value="1" id="removeEventImage">
            <label class="form-check-label small" for="removeEventImage">Quitar imagen</label>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <a href="tickets.php" target="_blank" class="btn btn-outline-secondary btn-sm mt-3">
      <i class="bi bi-box-arrow-up-right me-1"></i>Ver revisión de solicitudes
    </a>
  </div>

  <div class="admin-card">
    <h2 class="h5 mb-3">Contacto</h2>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Teléfono</label>
        <input type="text" name="phone" class="form-control" value="<?= v('phone') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">WhatsApp (solo números, con código de país)</label>
        <input type="text" name="whatsapp" class="form-control" value="<?= v('whatsapp') ?>" placeholder="593999999999">
      </div>
      <div class="col-md-4">
        <label class="form-label">Correo</label>
        <input type="email" name="email" class="form-control" value="<?= v('email') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Dirección</label>
        <input type="text" name="address" class="form-control" value="<?= v('address') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Coordenadas exactas del mapa (opcional)</label>
        <input type="text" name="map_coordinates" class="form-control" value="<?= v('map_coordinates') ?>"
               placeholder="Ej: -2.869132,-78.9826157">
        <div class="form-text">
          Si las llenas, el mapa de Contacto usa este punto exacto en vez de buscar la dirección de texto.
          Para conseguirlas: abre Google Maps, clic derecho en tu ubicación exacta y copia las coordenadas que aparecen arriba.
        </div>
      </div>
    </div>
  </div>

  <div class="admin-card">
    <h2 class="h5 mb-3">Redes sociales</h2>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-facebook me-1"></i>Facebook</label>
        <input type="url" name="facebook" class="form-control" value="<?= v('facebook') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-instagram me-1"></i>Instagram</label>
        <input type="url" name="instagram" class="form-control" value="<?= v('instagram') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-twitter-x me-1"></i>Twitter / X</label>
        <input type="url" name="twitter" class="form-control" value="<?= v('twitter') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-youtube me-1"></i>YouTube</label>
        <input type="url" name="youtube" class="form-control" value="<?= v('youtube') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-tiktok me-1"></i>TikTok</label>
        <input type="url" name="tiktok" class="form-control" value="<?= v('tiktok') ?>">
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-danger px-4">Guardar cambios</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
