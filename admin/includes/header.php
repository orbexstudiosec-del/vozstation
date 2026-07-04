<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/../../includes/functions.php';

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de administración - <?= e(get_setting('site_name', 'VozStation')) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 col-lg-2 admin-sidebar">
      <div class="brand"><i class="bi bi-broadcast me-2"></i>VozStation Admin</div>
      <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-sliders me-2"></i>Configuración
      </a>
      <a href="slides.php" class="<?= $current_page === 'slides.php' ? 'active' : '' ?>">
        <i class="bi bi-images me-2"></i>Slider principal
      </a>
      <a href="schedule.php" class="<?= $current_page === 'schedule.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar-week me-2"></i>Programación
      </a>
      <a href="password.php" class="<?= $current_page === 'password.php' ? 'active' : '' ?>">
        <i class="bi bi-key me-2"></i>Cambiar contraseña
      </a>
      <a href="../index.php" target="_blank">
        <i class="bi bi-box-arrow-up-right me-2"></i>Ver sitio
      </a>
      <a href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Cerrar sesión</a>
    </div>
    <div class="col-md-9 col-lg-10 admin-content">
