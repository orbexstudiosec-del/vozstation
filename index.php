<?php
require_once __DIR__ . '/includes/functions.php';

global $DAYS_ES;

$settings = get_all_settings();
$schedule = get_schedule();

$site_name   = get_setting('site_name', 'VozStation');
$tagline     = get_setting('tagline');
$description = get_setting('description');
$about_text  = get_setting('about_text');
$logo        = get_setting('logo');
$hero_image  = get_setting('hero_image');
$stream_url  = get_setting('stream_url');
$phone       = get_setting('phone');
$whatsapp    = get_setting('whatsapp');
$email       = get_setting('email');
$address     = get_setting('address');
$primary_color   = get_setting('primary_color', '#e50914');
$secondary_color = get_setting('secondary_color', '#1e6fe8');

$socials = [
    'facebook'  => ['icon' => 'bi-facebook', 'label' => 'Facebook'],
    'instagram' => ['icon' => 'bi-instagram', 'label' => 'Instagram'],
    'twitter'   => ['icon' => 'bi-twitter-x', 'label' => 'Twitter/X'],
    'youtube'   => ['icon' => 'bi-youtube', 'label' => 'YouTube'],
    'tiktok'    => ['icon' => 'bi-tiktok', 'label' => 'TikTok'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($site_name) ?><?= $tagline ? ' - ' . e($tagline) : '' ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16.png">
<link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicon-180.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>" rel="stylesheet">
<style>:root { --radio-accent: <?= e($primary_color) ?>; --radio-accent-blue: <?= e($secondary_color) ?>; }</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-vozstation fixed-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="#inicio">
      <?php if ($logo): ?>
        <span class="brand-logo-chip">
          <img src="<?= e($logo) ?>" alt="<?= e($site_name) ?>">
        </span>
      <?php else: ?>
        <i class="bi bi-mic-fill brand-mic-icon"></i>
        <span class="brand-wordmark">
          <span class="brand-name"><?= render_brand_wordmark($site_name) ?></span>
          <span class="brand-badge d-none d-lg-inline-block">Radio Online</span>
        </span>
      <?php endif; ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto gap-lg-3 align-items-lg-center">
        <li class="nav-item"><a class="nav-link active" href="#inicio">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="#programacion">Programación</a></li>
        <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
        <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
        <li class="nav-item">
          <div class="social-icons navbar-social mb-0 mt-3 mt-lg-0">
            <?php foreach ($socials as $key => $meta): ?>
              <?php if (!empty($settings[$key])): ?>
                <a href="<?= e($settings[$key]) ?>" target="_blank" rel="noopener" aria-label="<?= e($meta['label']) ?>">
                  <i class="bi <?= $meta['icon'] ?>"></i>
                </a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<header class="hero <?= $hero_image ? 'has-bg-image' : '' ?>" id="inicio"
        <?php if ($hero_image): ?>
        style="background-image: linear-gradient(90deg, rgba(8,9,12,.85) 0%, rgba(8,9,12,.55) 45%, rgba(8,9,12,.25) 100%), url('<?= e($hero_image) ?>'); background-size: cover; background-position: center;"
        <?php endif; ?>>
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="live-badge mb-3"><span class="live-dot"></span> ESCÚCHANOS EN VIVO 24/7</span>
        <h1 class="mt-3"><?= e($site_name) ?></h1>
        <p class="tagline"><?= e($tagline) ?></p>
        <p class="text-secondary"><?= e($description) ?></p>
        <div class="d-flex gap-3 mt-4 flex-wrap align-items-center">
          <a href="#programacion" class="btn btn-outline-light btn-lg">Ver programación</a>
          <div class="social-icons mb-0">
            <?php foreach ($socials as $key => $meta): ?>
              <?php if (!empty($settings[$key])): ?>
                <a href="<?= e($settings[$key]) ?>" target="_blank" rel="noopener" aria-label="<?= e($meta['label']) ?>">
                  <i class="bi <?= $meta['icon'] ?>"></i>
                </a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="player-card">
          <div class="d-flex align-items-center gap-3">
            <button id="play-btn" class="play-btn" aria-label="Reproducir">
              <i id="play-icon" class="bi bi-play-fill"></i>
            </button>
            <div class="flex-grow-1">
              <div class="now-playing-label">Transmisión en vivo</div>
              <div class="now-playing-title"><?= e($site_name) ?></div>
              <div id="player-status" class="text-secondary small">En pausa</div>
            </div>
          </div>
          <canvas id="visualizer" class="visualizer-canvas" height="64"></canvas>
          <div class="volume-row d-flex align-items-center gap-2 mt-3">
            <button id="mute-btn" class="mute-btn" aria-label="Silenciar">
              <i id="mute-icon" class="bi bi-volume-up text-secondary"></i>
            </button>
            <input type="range" class="form-range" id="volume-slider" min="0" max="1" step="0.05" value="0.8">
          </div>
          <audio id="radio-audio" data-stream-url="<?= e($stream_url) ?>" preload="none"></audio>
        </div>
      </div>
    </div>
  </div>
</header>

<section id="programacion">
  <div class="container">
    <h2 class="section-title">Programación</h2>
    <p class="section-subtitle">Nuestra parrilla semanal, día por día.</p>

    <ul class="nav nav-pills mb-4 flex-wrap gap-2" id="dayTabs" role="tablist">
      <?php $first = true; foreach ($DAYS_ES as $dayNum => $dayName): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $first ? 'active' : '' ?>" id="day-<?= $dayNum ?>-tab" data-bs-toggle="pill"
                  data-bs-target="#day-<?= $dayNum ?>" type="button" role="tab">
            <?= e($dayName) ?>
          </button>
        </li>
      <?php $first = false; endforeach; ?>
    </ul>

    <div class="tab-content">
      <?php $first = true; foreach ($DAYS_ES as $dayNum => $dayName): ?>
        <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="day-<?= $dayNum ?>" role="tabpanel">
          <?php if (!empty($schedule[$dayNum])): ?>
            <div class="row g-4">
              <?php foreach ($schedule[$dayNum] as $item): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                  <div class="program-card">
                    <div class="program-card-media">
                      <?php if (!empty($item['image'])): ?>
                        <img src="<?= e($item['image']) ?>" alt="<?= e($item['program_name']) ?>">
                      <?php else: ?>
                        <div class="program-card-fallback">
                          <i class="bi bi-broadcast"></i>
                        </div>
                      <?php endif; ?>
                      <span class="program-card-time">
                        <?= format_time($item['start_time']) ?> - <?= format_time($item['end_time']) ?>
                      </span>
                    </div>
                    <div class="program-card-body">
                      <div class="program-card-title"><?= e($item['program_name']) ?></div>
                      <?php if (!empty($item['host_name'])): ?>
                        <div class="program-card-host"><i class="bi bi-mic-fill me-1"></i><?= e($item['host_name']) ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="schedule-card">
              <p class="text-secondary mb-0">Sin programación registrada para este día.</p>
            </div>
          <?php endif; ?>
        </div>
      <?php $first = false; endforeach; ?>
    </div>
  </div>
</section>

<section id="nosotros" class="about-section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <h2 class="section-title">Nosotros</h2>
        <p class="text-secondary" style="white-space: pre-line;"><?= e($about_text) ?></p>
      </div>
      <div class="col-lg-6">
        <div class="schedule-card">
          <div class="schedule-day">¿Por qué escucharnos?</div>
          <div class="schedule-item"><div class="program"><i class="bi bi-broadcast me-2"></i>Transmisión 24/7</div></div>
          <div class="schedule-item"><div class="program"><i class="bi bi-music-note-beamed me-2"></i>La mejor música</div></div>
          <div class="schedule-item"><div class="program"><i class="bi bi-mic me-2"></i>Locutores en vivo</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="contacto">
  <div class="container">
    <h2 class="section-title">Contacto</h2>
    <p class="section-subtitle">Síguenos en nuestras redes o escríbenos directamente.</p>
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="contact-card">
          <p><i class="bi bi-telephone me-2"></i><?= e($phone) ?></p>
          <p><i class="bi bi-envelope me-2"></i><?= e($email) ?></p>
          <p><i class="bi bi-geo-alt me-2"></i><?= e($address) ?></p>
          <?php if ($whatsapp): ?>
            <a class="btn btn-accent mt-2" target="_blank" rel="noopener"
               href="https://wa.me/<?= e($whatsapp) ?>">
              <i class="bi bi-whatsapp me-1"></i> Escríbenos por WhatsApp
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <?php if ($address): ?>
          <div class="map-embed">
            <iframe
              src="https://www.google.com/maps?q=<?= urlencode($address) ?>&output=embed"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              allowfullscreen></iframe>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
    <div>&copy; <?= date('Y') ?> <?= e($site_name) ?>. Todos los derechos reservados.</div>
    <div class="social-icons mb-0">
      <?php foreach ($socials as $key => $meta): ?>
        <?php if (!empty($settings[$key])): ?>
          <a href="<?= e($settings[$key]) ?>" target="_blank" rel="noopener" aria-label="<?= e($meta['label']) ?>">
            <i class="bi <?= $meta['icon'] ?>"></i>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</footer>

<div id="mini-player" class="mini-player">
  <div class="container d-flex align-items-center gap-3">
    <button id="mini-play-btn" class="mini-play-btn" aria-label="Reproducir">
      <i id="mini-play-icon" class="bi bi-play-fill"></i>
    </button>
    <div class="mini-player-info flex-grow-1">
      <div class="mini-player-title"><?= e($site_name) ?></div>
      <div id="mini-player-status" class="mini-player-status">En pausa</div>
    </div>
    <span class="live-badge d-none d-sm-inline-flex mb-0"><span class="live-dot"></span> EN VIVO</span>
  </div>
</div>

<?php if ($whatsapp): ?>
  <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener"
     class="whatsapp-float" aria-label="Escríbenos por WhatsApp">
    <i class="bi bi-whatsapp"></i>
  </a>
<?php endif; ?>

<script>
  window.VOZSTATION_CONFIG = {
    siteName: <?= json_encode($site_name) ?>,
    tagline: <?= json_encode($tagline) ?>,
    logo: <?= json_encode($logo ?: '') ?>
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
</body>
</html>
