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
        'stream_url', 'stream_format', 'phone', 'whatsapp', 'email', 'address',
        'facebook', 'instagram', 'twitter', 'youtube', 'tiktok', 'primary_color', 'secondary_color',
    ];

    foreach ($fields as $field) {
        $value = trim($_POST[$field] ?? '');
        save_setting($field, $value);
    }

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
    </div>
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
