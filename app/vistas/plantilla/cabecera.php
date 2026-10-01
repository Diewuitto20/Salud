<?php defined('RAIZ') or exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo) ?> · ReActiva-T</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/estilos.css?v=<?= filemtime(RAIZ . '/css/estilos.css') ?>">
</head>
<body>
<a class="saltar" href="#contenido">Saltar al contenido</a>
<?php if (!$u): ?>
  <div class="perfiles">
    <div class="contenedor">
      <a href="index.php" class="<?= $zonaEmpresas ? '' : 'activo' ?>">Para ti</a>
      <a href="empresas.php" class="<?= $zonaEmpresas ? 'activo' : '' ?>">Para empresas</a>
    </div>
  </div>
<?php endif; ?>
<header class="barra">
  <div class="contenedor barra-dentro">
    <a class="marca" href="<?= esEmpresa() ? 'empresa.php' : 'index.php' ?>">ReActiva<span class="marca-t">-T</span><?= $zonaEmpresas ? ' <span class="marca-empresas">Empresas</span>' : '' ?></a>
    <nav class="menu" aria-label="Principal">
      <?php foreach ($enlaces as $archivo => $texto): ?>
        <a href="<?= $archivo ?>" class="<?= $actual === $archivo ? 'activo' : '' ?>"<?= $actual === $archivo ? ' aria-current="page"' : '' ?>><?= $texto ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="cuenta">
      <?php if ($u): ?>
        <span class="quien"><?= e($u['nombre']) ?></span>
        <form method="post" action="salir.php" class="form-salir"><?= campoToken() ?><button type="submit">Salir</button></form>
      <?php else: ?>
        <a href="<?= $zonaEmpresas ? 'empresas.php#acceso' : 'entrar.php' ?>">Entrar</a>
      <?php endif; ?>
      <?php if (!$zonaEmpresas): ?>
        <a class="ayuda-ya" href="ayuda.php">Necesito ayuda</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main id="contenido">
<?= mostrarAviso() ?>
