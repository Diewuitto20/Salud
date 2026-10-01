<?php
require 'includes/funciones.php';
require 'includes/apoyos_datos.php';
$titulo = 'Apoyos del gobierno';
$estado = estadoActual();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir('persona');
    validarToken();
    $apoyo = $_POST['apoyo'] ?? '';
    if (isset(APOYOS[$apoyo])) {
        $borrado = bd()->prepare('DELETE FROM apoyos_guardados WHERE usuario_id = ? AND apoyo = ?');
        $borrado->execute([usuario()['id'], $apoyo]);
        if (!$borrado->rowCount()) {
            bd()->prepare('INSERT INTO apoyos_guardados (usuario_id, apoyo) VALUES (?, ?)')->execute([usuario()['id'], $apoyo]);
            aviso('Guardado en <a href="avance.php#tramites">Mi ruta</a> con la lista de documentos que necesitas.');
        }
    }
    redirigir('apoyos.php#apoyo-' . $apoyo);
}

$visibles = array_filter(APOYOS, fn($a) => $a['estado'] === null || $a['estado'] === $estado);
$guardados = [];
if (esPersona()) {
    $st = bd()->prepare('SELECT apoyo FROM apoyos_guardados WHERE usuario_id = ?');
    $st->execute([usuario()['id']]);
    $guardados = $st->fetchAll(PDO::FETCH_COLUMN);
}
require 'includes/cabecera.php';
?>
<section class="seccion">
  <div class="contenedor">
    <div class="encabezado">
      <div>
        <h1 class="titulo-pagina">Apoyos del gobierno</h1>
        <p class="suave">Programas públicos para quien perdió su empleo, ordenados de lo más inmediato a lo que conviene usar al final.</p>
      </div>
      <?= selectorEstado($estado) ?>
    </div>

    <?php if (!$estado): ?>
      <p class="invitacion">Elige tu estado para ver también los apoyos de tu región.</p>
    <?php elseif ($estado === 'otro'): ?>
      <p class="invitacion">Por ahora tenemos apoyos estatales de Puebla, Veracruz, Oaxaca y Tlaxcala. Te mostramos los nacionales.</p>
    <?php endif; ?>

    <details class="guia-apoyos" open>
      <summary>¿Cuáles me corresponden? Responde 4 preguntas</summary>
      <form class="mini-cuestionario" id="mini-cuestionario">
        <label>Tu edad<input type="number" name="edad" min="15" max="100" inputmode="numeric"></label>
        <label>¿Estudias actualmente?
          <select name="estudia"><option value="">Elige</option><option value="no">No</option><option value="si">Sí</option></select>
        </label>
        <label>¿Tenías IMSS en tu último trabajo?
          <select name="imss"><option value="">Elige</option><option value="si">Sí</option><option value="no">No</option></select>
        </label>
        <label>¿Cuánto llevas sin empleo?
          <select name="dias"><option value="">Elige</option><option value="poco">Menos de 46 días</option><option value="mucho">46 días o más</option></select>
        </label>
      </form>
      <p class="chico" id="resumen-apoyos" aria-live="polite">Las tarjetas que te pueden corresponder se marcarán en verde.</p>
    </details>

    <div class="rejilla-3">
      <?php foreach ($visibles as $clave => $a): ?>
        <article class="tarjeta apoyo-gob <?= $clave === 'afore' ? 'apoyo-ultimo' : '' ?>" id="apoyo-<?= $clave ?>" data-regla="<?= $a['regla'] ?>">
          <?php if (!empty($a['advertencia'])): ?>
            <p class="advertencia-fuerte"><b>Atención:</b> <?= e($a['advertencia']) ?></p>
          <?php endif; ?>
          <span class="sello sello-ok marca-corresponde" hidden>Te puede corresponder</span>
          <?php if ($a['estado']): ?><span class="sello">Solo en <?= e(ESTADOS[$a['estado']]) ?></span><?php endif; ?>
          <h3><?= e($a['nombre']) ?></h3>
          <p class="institucion"><?= e($a['quien']) ?></p>
          <p><b>Para quién:</b> <?= e($a['para']) ?></p>
          <p><?= e($a['que']) ?></p>
          <details class="documentos">
            <summary>Documentos que necesitas</summary>
            <ul><?php foreach ($a['documentos'] as $d): ?><li><?= e($d) ?></li><?php endforeach; ?></ul>
          </details>
          <div class="acciones-curso">
            <a class="boton linea chico-boton" href="<?= e($a['enlace']) ?>" target="_blank" rel="noopener">Sitio oficial</a>
            <?php if (esPersona()): ?>
              <form method="post">
                <?= campoToken() ?>
                <input type="hidden" name="apoyo" value="<?= $clave ?>">
                <button class="boton chico-boton <?= in_array($clave, $guardados, true) ? 'guardado' : '' ?>" type="submit"><?= in_array($clave, $guardados, true) ? 'Lo quiero ✓' : 'Lo quiero' ?></button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="chico nota-legal">Información revisada en septiembre de 2026. Los montos, requisitos y fechas cambian cada año: confirma siempre en el sitio oficial. Todos los trámites son gratuitos.</p>
  </div>
</section>
<?php require 'includes/pie.php'; ?>
