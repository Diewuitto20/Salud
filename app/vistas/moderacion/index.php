<?php defined('RAIZ') or exit; ?>
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
