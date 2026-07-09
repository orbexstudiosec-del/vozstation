<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if (in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'aprobado' : 'rechazado';
        $stmt = $pdo->prepare('UPDATE tickets SET status = :status, reviewed_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
        header('Location: tickets.php?updated=1');
        exit;
    }
}

if (isset($_GET['updated'])) {
    $message = 'Solicitud actualizada correctamente.';
}

$statusFilter = $_GET['status'] ?? '';
if (in_array($statusFilter, ['pendiente', 'aprobado', 'rechazado'], true)) {
    $stmt = $pdo->prepare('SELECT * FROM tickets WHERE status = :status ORDER BY created_at DESC');
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt = $pdo->query('SELECT * FROM tickets ORDER BY created_at DESC');
}
$allTickets = $stmt->fetchAll();

$counts = ['pendiente' => 0, 'aprobado' => 0, 'rechazado' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) as total FROM tickets GROUP BY status')->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}

$statusLabels = [
    'pendiente' => ['label' => 'Pendiente', 'class' => 'bg-warning text-dark'],
    'aprobado' => ['label' => 'Aprobado', 'class' => 'bg-success'],
    'rechazado' => ['label' => 'Rechazado', 'class' => 'bg-danger'],
];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Tickets</h1>

<?php if ($message): ?>
  <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="admin-card mb-0 text-center">
      <div class="h4 mb-0"><?= $counts['pendiente'] ?></div>
      <div class="text-muted small">Pendientes de revisión</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card mb-0 text-center">
      <div class="h4 mb-0"><?= $counts['aprobado'] ?></div>
      <div class="text-muted small">Aprobados / liberados</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card mb-0 text-center">
      <div class="h4 mb-0"><?= $counts['rechazado'] ?></div>
      <div class="text-muted small">Rechazados</div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Solicitudes</h2>
    <div class="btn-group btn-group-sm">
      <a href="tickets.php" class="btn btn-outline-secondary <?= $statusFilter === '' ? 'active' : '' ?>">Todas</a>
      <a href="tickets.php?status=pendiente" class="btn btn-outline-secondary <?= $statusFilter === 'pendiente' ? 'active' : '' ?>">Pendientes</a>
      <a href="tickets.php?status=aprobado" class="btn btn-outline-secondary <?= $statusFilter === 'aprobado' ? 'active' : '' ?>">Aprobados</a>
      <a href="tickets.php?status=rechazado" class="btn btn-outline-secondary <?= $statusFilter === 'rechazado' ? 'active' : '' ?>">Rechazados</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Código</th>
          <th>Comprador</th>
          <th>Contacto</th>
          <th>Cant.</th>
          <th>Total</th>
          <th>Comprobante</th>
          <th>Estado</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$allTickets): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No hay solicitudes todavía.</td></tr>
        <?php endif; ?>
        <?php foreach ($allTickets as $t): ?>
          <tr>
            <td><code><?= e($t['code']) ?></code></td>
            <td><?= e($t['buyer_name']) ?></td>
            <td>
              <?= e($t['buyer_phone']) ?>
              <?php if (!empty($t['buyer_email'])): ?><br><span class="text-muted small"><?= e($t['buyer_email']) ?></span><?php endif; ?>
            </td>
            <td><?= (int) $t['quantity'] ?></td>
            <td>$<?= number_format((float) $t['total_price'], 2) ?></td>
            <td>
              <a href="view-proof.php?file=<?= urlencode(basename($t['payment_proof'])) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-image me-1"></i>Ver
              </a>
            </td>
            <td>
              <span class="badge <?= $statusLabels[$t['status']]['class'] ?? 'bg-secondary' ?>">
                <?= e($statusLabels[$t['status']]['label'] ?? $t['status']) ?>
              </span>
            </td>
            <td class="text-end">
              <?php if ($t['status'] === 'pendiente'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="approve">
                  <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Aprobar</button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('¿Rechazar esta solicitud?');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="reject">
                  <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                </form>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
