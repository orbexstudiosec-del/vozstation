<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/functions.php';

global $DAYS_ES;

$pdo = get_db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM schedule WHERE id = :id');
        $stmt->execute(['id' => $id]);
        header('Location: schedule.php?deleted=1');
        exit;
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $day = (int) ($_POST['day_of_week'] ?? 0);
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        $program = trim($_POST['program_name'] ?? '');
        $host = trim($_POST['host_name'] ?? '');
        $image = trim($_POST['existing_image'] ?? '');

        if ($day < 1 || $day > 7 || !$start || !$end || $program === '') {
            $error = 'Completa día, horario y nombre del programa.';
        } elseif (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'webp' => 1];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!array_key_exists($ext, $allowed)) {
                $error = 'Formato de imagen no permitido. Usa JPG, PNG o WEBP.';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $error = 'La imagen no debe superar 2MB.';
            } else {
                $uploadDir = __DIR__ . '/../assets/img/uploads/';
                $filename = 'program_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $image = 'assets/img/uploads/' . $filename;
                } else {
                    $error = 'No se pudo subir la imagen. Verifica permisos de la carpeta assets/img/uploads.';
                }
            }
        }

        if (!$error) {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE schedule SET day_of_week = :day, start_time = :start, end_time = :end,
                     program_name = :program, host_name = :host, image = :image WHERE id = :id'
                );
                $stmt->execute(['day' => $day, 'start' => $start, 'end' => $end, 'program' => $program, 'host' => $host, 'image' => $image ?: null, 'id' => $id]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO schedule (day_of_week, start_time, end_time, program_name, host_name, image, sort_order)
                     VALUES (:day, :start, :end, :program, :host, :image, 0)'
                );
                $stmt->execute(['day' => $day, 'start' => $start, 'end' => $end, 'program' => $program, 'host' => $host, 'image' => $image ?: null]);
            }
            header('Location: schedule.php?saved=1');
            exit;
        }
    }
}

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM schedule WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editItem = $stmt->fetch();
}

if (isset($_GET['saved'])) {
    $message = 'Programa guardado correctamente.';
}
if (isset($_GET['deleted'])) {
    $message = 'Programa eliminado.';
}

$allSchedule = $pdo->query(
    'SELECT * FROM schedule ORDER BY day_of_week ASC, start_time ASC'
)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Programación</h1>

<?php if ($message): ?>
  <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="admin-card">
  <h2 class="h5 mb-3"><?= $editItem ? 'Editar programa' : 'Agregar programa' ?></h2>
  <form method="post" class="row g-3" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $editItem ? (int) $editItem['id'] : 0 ?>">
    <input type="hidden" name="existing_image" value="<?= $editItem ? e($editItem['image']) : '' ?>">

    <div class="col-md-3">
      <label class="form-label">Día</label>
      <select name="day_of_week" class="form-select" required>
        <option value="">Selecciona</option>
        <?php foreach ($DAYS_ES as $num => $name): ?>
          <option value="<?= $num ?>" <?= ($editItem && (int) $editItem['day_of_week'] === $num) ? 'selected' : '' ?>>
            <?= e($name) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Inicio</label>
      <input type="time" name="start_time" class="form-control"
             value="<?= $editItem ? substr($editItem['start_time'], 0, 5) : '' ?>" required>
    </div>
    <div class="col-md-2">
      <label class="form-label">Fin</label>
      <input type="time" name="end_time" class="form-control"
             value="<?= $editItem ? substr($editItem['end_time'], 0, 5) : '' ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Programa</label>
      <input type="text" name="program_name" class="form-control"
             value="<?= $editItem ? e($editItem['program_name']) : '' ?>" required>
    </div>
    <div class="col-md-2">
      <label class="form-label">Locutor</label>
      <input type="text" name="host_name" class="form-control"
             value="<?= $editItem ? e($editItem['host_name']) : '' ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Imagen del programa (opcional)</label>
      <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
      <?php if ($editItem && !empty($editItem['image'])): ?>
        <img src="../<?= e($editItem['image']) ?>" class="logo-preview mt-2" alt="Imagen actual">
      <?php endif; ?>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-danger"><?= $editItem ? 'Actualizar' : 'Agregar' ?></button>
      <?php if ($editItem): ?>
        <a href="schedule.php" class="btn btn-outline-secondary">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-card">
  <h2 class="h5 mb-3">Programas registrados</h2>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Imagen</th>
          <th>Día</th>
          <th>Horario</th>
          <th>Programa</th>
          <th>Locutor</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$allSchedule): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No hay programas registrados todavía.</td></tr>
        <?php endif; ?>
        <?php foreach ($allSchedule as $item): ?>
          <tr>
            <td>
              <?php if (!empty($item['image'])): ?>
                <img src="../<?= e($item['image']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td><?= e($DAYS_ES[(int) $item['day_of_week']] ?? '') ?></td>
            <td><?= format_time($item['start_time']) ?> - <?= format_time($item['end_time']) ?></td>
            <td><?= e($item['program_name']) ?></td>
            <td><?= e($item['host_name']) ?></td>
            <td class="text-end">
              <a href="schedule.php?edit=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este programa?');">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
