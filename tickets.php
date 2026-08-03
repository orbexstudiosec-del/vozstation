<?php
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function ticket_csrf_token(): string
{
    if (empty($_SESSION['ticket_csrf'])) {
        $_SESSION['ticket_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ticket_csrf'];
}

$site_name       = get_setting('site_name', 'VozStation');
$logo            = get_setting('logo');
$primary_color   = get_setting('primary_color', '#e50914');
$secondary_color = get_setting('secondary_color', '#1e6fe8');
$whatsapp        = get_setting('whatsapp');
$video_stream_url = get_setting('video_stream_url');

$ticketsEnabled  = get_setting('tickets_enabled') === '1';
$eventName       = get_setting('tickets_event_name');
$eventDate       = get_setting('tickets_event_date');
$ticketPrice     = (float) get_setting('tickets_price', '0');
$eventDescription = get_setting('tickets_description');
$eventImage      = get_setting('tickets_event_image');

$settings = get_all_settings();
$socials = [
    'facebook'  => ['icon' => 'bi-facebook', 'label' => 'Facebook'],
    'instagram' => ['icon' => 'bi-instagram', 'label' => 'Instagram'],
    'twitter'   => ['icon' => 'bi-twitter-x', 'label' => 'Twitter/X'],
    'youtube'   => ['icon' => 'bi-youtube', 'label' => 'YouTube'],
    'tiktok'    => ['icon' => 'bi-tiktok', 'label' => 'TikTok'],
];

$error = '';
$successCode = null;

if ($ticketsEnabled && $eventName && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['ticket_csrf'] ?? '', $token)) {
        $error = 'Tu sesión expiró. Recarga la página e intenta de nuevo.';
    } elseif (!empty($_POST['website'])) {
        // campo trampa para bots: si viene lleno, se ignora la solicitud
        $error = 'No se pudo procesar la solicitud.';
    } else {
        $buyerName  = trim($_POST['buyer_name'] ?? '');
        $buyerEmail = trim($_POST['buyer_email'] ?? '');
        $buyerPhone = trim($_POST['buyer_phone'] ?? '');
        $quantity   = (int) ($_POST['quantity'] ?? 0);

        if ($buyerName === '' || $buyerPhone === '') {
            $error = 'Completa tu nombre y teléfono/WhatsApp.';
        } elseif ($quantity < 1 || $quantity > 20) {
            $error = 'La cantidad de entradas debe ser entre 1 y 20.';
        } else {
            $upload = handle_file_upload(
                'payment_proof',
                'ticket',
                ['jpg', 'jpeg', 'png', 'pdf'],
                'assets/uploads/tickets/',
                5 * 1024 * 1024,
                'comprobante'
            );

            if ($upload['error']) {
                $error = $upload['error'];
            } elseif (!$upload['path']) {
                $error = 'Sube el comprobante de pago.';
            } else {
                $total = round($quantity * $ticketPrice, 2);
                $code = generate_ticket_code();

                $pdo = get_db();
                $stmt = $pdo->prepare(
                    'INSERT INTO tickets (code, buyer_name, buyer_email, buyer_phone, quantity, unit_price, total_price, payment_proof, status)
                     VALUES (:code, :name, :email, :phone, :qty, :unit, :total, :proof, \'pendiente\')'
                );
                $stmt->execute([
                    'code' => $code,
                    'name' => $buyerName,
                    'email' => $buyerEmail ?: null,
                    'phone' => $buyerPhone,
                    'qty' => $quantity,
                    'unit' => $ticketPrice,
                    'total' => $total,
                    'proof' => $upload['path'],
                ]);

                unset($_SESSION['ticket_csrf']);
                $successCode = $code;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entradas - <?= e($site_name) ?></title>
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>" rel="stylesheet">
<style>:root { --radio-accent: <?= e($primary_color) ?>; --radio-accent-blue: <?= e($secondary_color) ?>; }</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-vozstation is-scrolled fixed-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <?php if ($logo): ?>
        <span class="brand-logo-chip"><img src="<?= e($logo) ?>" alt="<?= e($site_name) ?>"></span>
        <span class="brand-badge d-none d-lg-inline-block ms-2"><?= e($site_name) ?> Radio Online</span>
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
        <li class="nav-item"><a class="nav-link" href="index.php#inicio">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#programacion">Programación</a></li>
        <?php if ($video_stream_url): ?>
          <li class="nav-item"><a class="nav-link" href="index.php#inicio">TV en vivo</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link active" href="tickets.php">Entradas</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#nosotros">Nosotros</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#contacto">Contacto</a></li>
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

<?php if (!$ticketsEnabled || !$eventName): ?>
  <section style="padding-top: 130px; min-height: 70vh; display: flex; align-items: center;">
    <div class="container" style="max-width: 560px;">
      <div class="ticket-card text-center">
        <i class="bi bi-ticket-perforated display-3 text-secondary mb-3 d-block"></i>
        <h1 class="h4">No hay venta de entradas activa</h1>
        <p class="text-secondary">Por ahora no tenemos ningún evento con entradas a la venta. Síguenos en nuestras redes para enterarte apenas abramos una nueva venta.</p>
        <div class="social-icons justify-content-center d-flex mt-3 mb-3">
          <?php foreach ($socials as $key => $meta): ?>
            <?php if (!empty($settings[$key])): ?>
              <a href="<?= e($settings[$key]) ?>" target="_blank" rel="noopener" aria-label="<?= e($meta['label']) ?>">
                <i class="bi <?= $meta['icon'] ?>"></i>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <a href="index.php" class="btn btn-accent">Volver al inicio</a>
      </div>
    </div>
  </section>

<?php elseif ($successCode): ?>
  <section style="padding-top: 130px; min-height: 70vh; display: flex; align-items: center;">
    <div class="container" style="max-width: 560px;">
      <div class="ticket-card text-center">
        <i class="bi bi-check-circle-fill display-3 mb-3 d-block" style="color:#1fae5c;"></i>
        <h1 class="h4">¡Solicitud recibida!</h1>
        <p class="text-secondary mb-1">Tu código de referencia es:</p>
        <div class="ticket-code"><?= e($successCode) ?></div>
        <div class="ticket-summary-mini mt-3">
          <?= (int) ($_POST['quantity'] ?? 1) ?> entrada<?= ((int) ($_POST['quantity'] ?? 1)) === 1 ? '' : 's' ?>
          para <strong><?= e($eventName) ?></strong>
        </div>
        <p class="text-secondary mt-3">
          Vamos a revisar tu comprobante de pago. Apenas se confirme, te contactaremos
          <?= $whatsapp ? 'por WhatsApp' : '' ?> para liberar tu(s) entrada(s). Guarda este código por cualquier consulta.
        </p>
        <?php if ($whatsapp): ?>
          <a class="btn btn-accent mt-2" target="_blank" rel="noopener"
             href="https://wa.me/<?= e($whatsapp) ?>?text=<?= urlencode('Hola, acabo de comprar entradas. Mi código es ' . $successCode) ?>">
            <i class="bi bi-whatsapp me-1"></i> Escribir por WhatsApp
          </a>
        <?php endif; ?>
      </div>
    </div>
  </section>

<?php else: ?>

  <header class="ticket-hero <?= $eventImage ? '' : 'ticket-hero-fallback' ?>"
          <?php if ($eventImage): ?>style="background-image: linear-gradient(90deg, rgba(8,9,12,.9) 15%, rgba(8,9,12,.35) 100%), url('<?= e($eventImage) ?>');"<?php endif; ?>>
    <div class="container">
      <span class="live-badge mb-3"><i class="bi bi-ticket-perforated-fill me-1"></i> ENTRADAS DISPONIBLES</span>
      <h1 class="ticket-hero-title"><?= e($eventName) ?></h1>
      <?php if ($eventDate): ?>
        <p class="ticket-hero-date"><i class="bi bi-calendar-event me-2"></i><?= e($eventDate) ?></p>
      <?php endif; ?>
    </div>
  </header>

  <section>
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-7">
          <?php if ($eventDescription): ?>
            <h2 class="h5 mb-3">Sobre el evento</h2>
            <p class="text-secondary" style="white-space: pre-line;"><?= e($eventDescription) ?></p>
          <?php endif; ?>

          <div class="ticket-trust-badges mt-4">
            <div class="ticket-trust-badge">
              <i class="bi bi-shield-check"></i>
              <span>Pago verificado a mano por el equipo de <?= e($site_name) ?></span>
            </div>
            <div class="ticket-trust-badge">
              <i class="bi bi-whatsapp"></i>
              <span>Confirmación y entrega por WhatsApp</span>
            </div>
            <div class="ticket-trust-badge">
              <i class="bi bi-ticket-perforated"></i>
              <span>Guarda tu código de referencia</span>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="ticket-card ticket-card-sticky">
            <?php if ($error): ?>
              <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="ticket-step-label">1. Elige la cantidad</div>
            <div class="ticket-cart">
              <div class="ticket-cart-item">
                <div>
                  <div class="ticket-cart-item-title"><i class="bi bi-ticket-perforated me-1"></i>Entrada general</div>
                  <div class="ticket-cart-item-price">
                    <?= $ticketPrice > 0 ? '$' . number_format($ticketPrice, 2) . ' c/u' : 'Gratuita' ?>
                  </div>
                </div>
                <div class="ticket-stepper">
                  <button type="button" class="ticket-stepper-btn" id="qtyMinus" aria-label="Quitar una entrada">&minus;</button>
                  <span class="ticket-stepper-value" id="qtyDisplay">1</span>
                  <button type="button" class="ticket-stepper-btn" id="qtyPlus" aria-label="Agregar una entrada">+</button>
                </div>
              </div>
              <div class="ticket-cart-summary">
                <span>Total (<span id="qtyLabel">1</span> entrada<span id="qtyPlural"></span>)</span>
                <strong id="ticketTotal"><?= $ticketPrice > 0 ? '$' . number_format($ticketPrice, 2) : 'Gratis' ?></strong>
              </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="mt-4">
              <input type="hidden" name="csrf_token" value="<?= ticket_csrf_token() ?>">
              <input type="hidden" name="quantity" id="ticketQuantity" value="<?= e($_POST['quantity'] ?? '1') ?>">
              <div class="d-none">
                <label>Sitio web (no llenar) <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
              </div>

              <div class="ticket-step-label mt-4">2. Tus datos</div>
              <div class="mb-3">
                <label class="form-label">Nombre completo</label>
                <input type="text" name="buyer_name" class="form-control" required
                       value="<?= e($_POST['buyer_name'] ?? '') ?>">
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Teléfono / WhatsApp</label>
                  <input type="text" name="buyer_phone" class="form-control" required
                         value="<?= e($_POST['buyer_phone'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Correo (opcional)</label>
                  <input type="email" name="buyer_email" class="form-control"
                         value="<?= e($_POST['buyer_email'] ?? '') ?>">
                </div>
              </div>

              <div class="ticket-step-label mt-4">3. Comprobante de pago</div>
              <div class="mb-3">
                <label class="form-label">JPG, PNG o PDF, máx. 5MB</label>
                <input type="file" name="payment_proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
              </div>

              <button type="submit" class="btn btn-accent btn-lg w-100">Enviar solicitud</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

      <script>
        (function () {
          var qtyInput = document.getElementById('ticketQuantity');
          var qtyDisplay = document.getElementById('qtyDisplay');
          var qtyLabel = document.getElementById('qtyLabel');
          var qtyPlural = document.getElementById('qtyPlural');
          var totalEl = document.getElementById('ticketTotal');
          var minusBtn = document.getElementById('qtyMinus');
          var plusBtn = document.getElementById('qtyPlus');
          if (!qtyInput || !minusBtn || !plusBtn) return;

          var price = <?= json_encode($ticketPrice) ?>;
          var MIN = 1;
          var MAX = 20;
          var qty = Math.min(MAX, Math.max(MIN, parseInt(qtyInput.value, 10) || 1));

          function render() {
            qtyInput.value = qty;
            qtyDisplay.textContent = qty;
            qtyLabel.textContent = qty;
            qtyPlural.textContent = qty === 1 ? '' : 's';
            minusBtn.disabled = qty <= MIN;
            plusBtn.disabled = qty >= MAX;
            if (totalEl) {
              totalEl.textContent = price > 0 ? '$' + (qty * price).toFixed(2) : 'Gratis';
            }
          }

          minusBtn.addEventListener('click', function () {
            qty = Math.max(MIN, qty - 1);
            render();
          });
          plusBtn.addEventListener('click', function () {
            qty = Math.min(MAX, qty + 1);
            render();
          });

          render();
        })();
      </script>
<?php endif; ?>

<footer>
  <div class="container text-center">
    &copy; <?= date('Y') ?> <?= e($site_name) ?>. Todos los derechos reservados.
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
