<?php
require_once __DIR__ . '/includes/functions.php';

global $DAYS_ES;

$settings = get_all_settings();
$schedule = get_schedule();
$slides   = get_slides();

$site_name   = get_setting('site_name', 'VozStation');
$tagline     = get_setting('tagline');
$description = get_setting('description');
$about_text  = get_setting('about_text');
$logo        = get_setting('logo');
$hero_image  = get_setting('hero_image');
$stream_url  = get_setting('stream_url');
$video_stream_url = get_setting('video_stream_url');
$ad_banner_text   = get_setting('ad_banner_text');
$phone       = get_setting('phone');
$whatsapp    = get_setting('whatsapp');
$email       = get_setting('email');
$address     = get_setting('address');
$map_coordinates = get_setting('map_coordinates');
$map_query   = $map_coordinates ?: $address;
$primary_color   = get_setting('primary_color', '#e50914');
$share_url  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/';
$share_text = $site_name . ($tagline ? ' - ' . $tagline : '');
$secondary_color = get_setting('secondary_color', '#1e6fe8');

$tickets_enabled  = get_setting('tickets_enabled') === '1';
$tickets_event    = get_setting('tickets_event_name');
$tickets_date     = get_setting('tickets_event_date');
$tickets_price    = (float) get_setting('tickets_price', '0');
$tickets_showcase = $tickets_enabled && $tickets_event;

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
        <span class="brand-badge d-none d-lg-inline-block ms-2"><?= e(spaced_site_name($site_name)) ?> Radio Online</span>
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
        <?php if ($video_stream_url): ?>
          <li class="nav-item"><a class="nav-link" href="#inicio" id="nav-tv-link">TV en vivo</a></li>
        <?php endif; ?>
        <?php if ($tickets_showcase): ?>
          <li class="nav-item"><a class="nav-link" href="tickets.php">Entradas</a></li>
        <?php endif; ?>
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

<?php if ($slides): ?>
<div id="cubeSlider" class="cube-slider">
  <div class="cube-stage" id="cubeStage">
    <div class="cube-face cube-face-front" id="cubeFaceFront"></div>
    <div class="cube-face cube-face-right" id="cubeFaceRight"></div>
    <div class="cube-face cube-face-left" id="cubeFaceLeft"></div>
  </div>
  <?php if (count($slides) > 1): ?>
    <button class="carousel-control-prev" type="button" id="cubePrevBtn">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Anterior</span>
    </button>
    <button class="carousel-control-next" type="button" id="cubeNextBtn">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Siguiente</span>
    </button>
    <div class="cube-indicators" id="cubeIndicators">
      <?php foreach ($slides as $i => $slide): ?>
        <button type="button" data-index="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script>
  window.VOZSTATION_SLIDES = <?= json_encode(array_map(function ($slide) {
      return [
          'image' => $slide['image'],
          'title' => $slide['title'],
          'subtitle' => $slide['subtitle'],
          'link' => $slide['link_url'],
      ];
  }, $slides), JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php endif; ?>

<header class="hero <?= $hero_image ? 'has-bg-image' : '' ?>" id="inicio"
        <?php if ($hero_image): ?>
        style="background-image: linear-gradient(90deg, rgba(8,9,12,.85) 0%, rgba(8,9,12,.55) 45%, rgba(8,9,12,.25) 100%), url('<?= e($hero_image) ?>'); background-size: cover; background-position: center;"
        <?php endif; ?>>
  <div class="hero-waveform" aria-hidden="true">
    <?php for ($i = 0; $i < 32; $i++): ?><span></span><?php endfor; ?>
  </div>
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="live-badge mb-3"><span class="live-dot"></span> ESCÚCHANOS EN VIVO 24/7</span>
        <h1 class="mt-3 hero-fade-2"><?= e($site_name) ?></h1>
        <p class="tagline hero-fade-3"><?= e($tagline) ?></p>
        <p class="text-secondary hero-fade-3"><?= e($description) ?></p>
        <div class="d-flex gap-3 mt-4 flex-wrap align-items-center hero-fade-4">
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
        <?php if ($video_stream_url): ?>
          <ul class="nav nav-pills player-tabs mb-3" id="playerTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="radio-tab-btn" data-bs-toggle="pill"
                      data-bs-target="#radio-tab-pane" type="button" role="tab">
                <i class="bi bi-broadcast me-1"></i> Radio
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tv-tab-btn" data-bs-toggle="pill"
                      data-bs-target="#tv-tab-pane" type="button" role="tab">
                <i class="bi bi-tv me-1"></i> TV en vivo
              </button>
            </li>
          </ul>
        <?php endif; ?>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="radio-tab-pane">
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
              <button type="button" id="unmute-hint" class="unmute-hint">
                <i class="bi bi-volume-mute-fill me-1"></i> Sonando sin audio — clic para activar el sonido
              </button>
              <div class="share-row d-flex align-items-center gap-2 mt-3">
                <span class="share-label">Compartir</span>
                <button id="native-share-btn" class="share-btn d-none" aria-label="Compartir"
                        data-share-url="<?= e($share_url) ?>" data-share-text="<?= e($share_text) ?>">
                  <i class="bi bi-share-fill"></i>
                </button>
                <a class="share-btn" target="_blank" rel="noopener" aria-label="Compartir en WhatsApp"
                   href="https://api.whatsapp.com/send?text=<?= urlencode($share_text . ' ' . $share_url) ?>">
                  <i class="bi bi-whatsapp"></i>
                </a>
                <a class="share-btn" target="_blank" rel="noopener" aria-label="Compartir en Facebook"
                   href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($share_url) ?>">
                  <i class="bi bi-facebook"></i>
                </a>
                <a class="share-btn" target="_blank" rel="noopener" aria-label="Compartir en X"
                   href="https://twitter.com/intent/tweet?text=<?= urlencode($share_text) ?>&url=<?= urlencode($share_url) ?>">
                  <i class="bi bi-twitter-x"></i>
                </a>
                <button id="copy-link-btn" class="share-btn" aria-label="Copiar enlace" data-share-url="<?= e($share_url) ?>">
                  <i class="bi bi-link-45deg"></i>
                </button>
              </div>
              <audio id="radio-audio" data-stream-url="<?= e($stream_url) ?>" preload="none"></audio>
            </div>
          </div>
          <?php if ($video_stream_url): ?>
            <div class="tab-pane fade" id="tv-tab-pane">
              <div class="video-player-card">
                <div class="video-wrapper">
                  <video id="tv-video" data-stream-url="<?= e($video_stream_url) ?>" playsinline controls></video>
                  <button id="tv-play-btn" class="video-play-overlay" aria-label="Reproducir video">
                    <i class="bi bi-play-fill"></i>
                  </button>
                </div>
                <div id="tv-status" class="text-secondary small mt-2">En pausa</div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</header>

<section id="programacion">
  <div class="container">
    <h2 class="section-title reveal">Programación</h2>
    <p class="section-subtitle reveal">Nuestra parrilla semanal, día por día.</p>

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

    <div class="tab-content reveal-trigger schedule-grid">
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

<?php if ($tickets_showcase): ?>
<section class="ad-banner">
  <div class="container d-flex flex-column flex-lg-row align-items-center justify-content-between gap-4 reveal">
    <div class="d-flex align-items-center gap-3">
      <i class="bi bi-ticket-perforated-fill ad-banner-icon"></i>
      <div>
        <h3 class="ad-banner-title mb-1"><?= e($tickets_event) ?></h3>
        <p class="mb-0">
          <?php if ($tickets_date): ?><?= e($tickets_date) ?><?php endif; ?>
          <?php if ($tickets_price > 0): ?> &middot; $<?= number_format($tickets_price, 2) ?> por entrada<?php endif; ?>
        </p>
      </div>
    </div>
    <a href="tickets.php" class="btn btn-lg ad-banner-btn">
      <i class="bi bi-ticket-perforated me-1"></i> Comprar entradas
    </a>
  </div>
</section>
<?php endif; ?>

<section id="nosotros" class="about-section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 reveal">
        <h2 class="section-title">Nosotros</h2>
        <p class="text-secondary" style="white-space: pre-line;"><?= e($about_text) ?></p>
      </div>
      <div class="col-lg-6 reveal">
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
    <h2 class="section-title reveal">Contacto</h2>
    <p class="section-subtitle reveal">Síguenos en nuestras redes o escríbenos directamente.</p>
    <div class="row g-4">
      <div class="col-lg-6 reveal">
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
          <div class="social-icons mt-3 mb-0">
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
      <div class="col-lg-6 reveal">
        <?php if ($map_query): ?>
          <div class="map-embed">
            <iframe
              src="https://www.google.com/maps?q=<?= urlencode($map_query) ?>&z=17&hl=es&output=embed"
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
  <div class="container text-center">
    &copy; <?= date('Y') ?> <?= e($site_name) ?>. Todos los derechos reservados.
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

<button id="back-to-top" class="back-to-top" aria-label="Volver arriba">
  <i class="bi bi-arrow-up"></i>
</button>

<div id="autoplay-popup" class="autoplay-popup">
  <div class="autoplay-popup-card">
    <i class="bi bi-music-note-beamed autoplay-popup-icon"></i>
    <h3 class="h5 mb-2">¿Escuchamos en vivo?</h3>
    <p class="text-secondary small mb-3">Dale play para sintonizar <?= e($site_name) ?> ahora mismo.</p>
    <button type="button" id="autoplay-popup-play" class="btn btn-accent btn-lg w-100">
      <i class="bi bi-play-fill me-1"></i> Reproducir música
    </button>
    <button type="button" id="autoplay-popup-dismiss" class="btn btn-link btn-sm mt-2 text-secondary">Ahora no</button>
  </div>
</div>

<script>
  window.VOZSTATION_CONFIG = {
    siteName: <?= json_encode($site_name) ?>,
    tagline: <?= json_encode($tagline) ?>,
    logo: <?= json_encode($logo ?: '') ?>
  };
</script>
<?php if ($video_stream_url): ?>
  <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.15/dist/hls.min.js"></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
</body>
</html>
