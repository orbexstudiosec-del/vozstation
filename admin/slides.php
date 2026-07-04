<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM slides WHERE id = :id');
        $stmt->execute(['id' => $id]);
        header('Location: slides.php?deleted=1');
        exit;
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $image = trim($_POST['existing_image'] ?? '');

        $upload = handle_image_upload('image', 'slide', 4 * 1024 * 1024);
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($upload['path']) {
            $image = $upload['path'];
        }

        if (!$error && $image === '') {
            $error = 'Sube una imagen para el slide.';
        }

        if (!$error) {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE slides SET image = :image, title = :title, subtitle = :subtitle,
                     link_url = :link_url, sort_order = :sort_order WHERE id = :id'
                );
                $stmt->execute([
                    'image' => $image, 'title' => $title ?: null, 'subtitle' => $subtitle ?: null,
                    'link_url' => $linkUrl ?: null, 'sort_order' => $sortOrder, 'id' => $id,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO slides (image, title, subtitle, link_url, sort_order)
                     VALUES (:image, :title, :subtitle, :link_url, :sort_order)'
                );
                $stmt->execute([
                    'image' => $image, 'title' => $title ?: null, 'subtitle' => $subtitle ?: null,
                    'link_url' => $linkUrl ?: null, 'sort_order' => $sortOrder,
                ]);
            }
            header('Location: slides.php?saved=1');
            exit;
        }
    }
}

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM slides WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editItem = $stmt->fetch();
}

if (isset($_GET['saved'])) {
    $message = 'Slide guardado correctamente.';
}
if (isset($_GET['deleted'])) {
    $message = 'Slide eliminado.';
}

$allSlides = $pdo->query('SELECT * FROM slides ORDER BY sort_order ASC, id ASC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Slider principal</h1>
<p class="text-muted">Carrusel de pantalla completa que aparece antes que todo lo demás, arriba del reproductor. Si no agregas ningún slide, esta sección no se muestra.</p>

<?php if ($message): ?>
  <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="admin-card">
  <h2 class="h5 mb-3"><?= $editItem ? 'Editar slide' : 'Agregar slide' ?></h2>
  <form method="post" class="row g-3" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $editItem ? (int) $editItem['id'] : 0 ?>">
    <input type="hidden" name="existing_image" value="<?= $editItem ? e($editItem['image']) : '' ?>">

    <div class="col-md-6">
      <label class="form-label">Imagen <?= $editItem ? '(deja vacío para mantener la actual)' : '' ?></label>
      <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg" <?= $editItem ? '' : 'required' ?>>
      <?php if ($editItem && !empty($editItem['image'])): ?>
        <img src="../<?= e($editItem['image']) ?>" class="logo-preview mt-2" alt="Imagen actual">
      <?php endif; ?>
      <div class="form-text">Tamaño recomendado: 1900 × 660 px (esa es la proporción del slider).</div>
    </div>
    <div class="col-md-2">
      <label class="form-label">Orden</label>
      <input type="number" name="sort_order" class="form-control" value="<?= $editItem ? (int) $editItem['sort_order'] : 0 ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Título (opcional)</label>
      <input type="text" name="title" class="form-control" value="<?= $editItem ? e($editItem['title']) : '' ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Subtítulo (opcional)</label>
      <input type="text" name="subtitle" class="form-control" value="<?= $editItem ? e($editItem['subtitle']) : '' ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Enlace del botón "Ver más" (opcional)</label>
      <input type="url" name="link_url" class="form-control" placeholder="https://..."
             value="<?= $editItem ? e($editItem['link_url']) : '' ?>">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-danger"><?= $editItem ? 'Actualizar' : 'Agregar' ?></button>
      <?php if ($editItem): ?>
        <a href="slides.php" class="btn btn-outline-secondary">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-card">
  <h2 class="h5 mb-3">Slides registrados</h2>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Imagen</th>
          <th>Título</th>
          <th>Orden</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$allSlides): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">No hay slides registrados todavía.</td></tr>
        <?php endif; ?>
        <?php foreach ($allSlides as $item): ?>
          <tr>
            <td>
              <img src="../<?= e($item['image']) ?>" alt="" style="width:80px;height:48px;object-fit:cover;border-radius:8px;">
            </td>
            <td><?= e($item['title']) ?: '<span class="text-muted">—</span>' ?></td>
            <td><?= (int) $item['sort_order'] ?></td>
            <td class="text-end">
              <a href="slides.php?edit=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este slide?');">
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
