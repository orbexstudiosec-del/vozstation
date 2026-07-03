<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT password FROM admins WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current, $admin['password'])) {
        $error = 'La contraseña actual no es correcta.';
    } elseif (strlen($new) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($new !== $confirm) {
        $error = 'Las contraseñas nuevas no coinciden.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE admins SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $hash, 'id' => $_SESSION['admin_id']]);
        $message = 'Contraseña actualizada correctamente.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Cambiar contraseña</h1>

<?php if ($message): ?>
  <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="admin-card" style="max-width: 480px;">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="mb-3">
      <label class="form-label">Contraseña actual</label>
      <input type="password" name="current_password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Nueva contraseña</label>
      <input type="password" name="new_password" class="form-control" minlength="8" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirmar nueva contraseña</label>
      <input type="password" name="confirm_password" class="form-control" minlength="8" required>
    </div>
    <button type="submit" class="btn btn-danger">Actualizar contraseña</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
