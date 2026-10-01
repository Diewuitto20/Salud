<?php
require 'includes/funciones.php';
if (!esLocal()) {
    http_response_code(403);
    exit('La moderación solo se puede abrir desde la computadora donde corre el servidor.');
}
$titulo = 'Moderación';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarToken();
    $nota = (int) ($_POST['nota'] ?? 0);
    if (($_POST['accion'] ?? '') === 'restaurar') {
        bd()->prepare('DELETE FROM denuncias WHERE nota_id = ?')->execute([$nota]);
        bd()->prepare('UPDATE notas SET oculta = 0 WHERE id = ?')->execute([$nota]);
        aviso('La nota volvió a publicarse.');
    }
    if (($_POST['accion'] ?? '') === 'eliminar') {
        bd()->prepare('DELETE FROM notas WHERE id = ?')->execute([$nota]);
        aviso('La nota se eliminó.');
    }
    redirigir('moderacion.php');
}
$denunciadas = bd()->query('SELECT n.id, n.texto, n.oculta, u.nombre, COUNT(d.usuario_id) AS reportes FROM notas n
    JOIN usuarios u ON u.id = n.usuario_id JOIN denuncias d ON d.nota_id = n.id GROUP BY n.id ORDER BY reportes DESC')->fetchAll();
require 'includes/cabecera.php';
?>
<section class="seccion">
  <div class="contenedor">
    <div class="encabezado">
      <div>
        <h1 class="titulo-pagina">Moderación del foro</h1>
        <p class="suave">Notas que la comunidad reportó. Con 3 reportes se ocultan solas hasta que las revises.</p>
      </div>
      <span class="chico">Solo visible desde esta computadora</span>
    </div>
    <?php foreach ($denunciadas as $d): ?>
      <article class="tarjeta reporte">
        <p><?= e($d['texto']) ?></p>
        <p class="chico">De <?= e($d['nombre']) ?> · <?= $d['reportes'] ?> reporte(s)<?= $d['oculta'] ? ' · <b>oculta</b>' : '' ?></p>
        <div class="acciones-curso">
          <form method="post"><?= campoToken() ?><input type="hidden" name="accion" value="restaurar"><input type="hidden" name="nota" value="<?= $d['id'] ?>"><button class="boton linea chico-boton" type="submit">Está bien, publicar</button></form>
          <form method="post"><?= campoToken() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="nota" value="<?= $d['id'] ?>"><button class="boton urgente chico-boton" type="submit">Eliminar</button></form>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$denunciadas): ?><p class="suave">No hay notas reportadas.</p><?php endif; ?>
  </div>
</section>
<?php require 'includes/pie.php'; ?>
