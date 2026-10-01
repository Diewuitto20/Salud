<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor">
    <div class="encabezado">
      <div>
        <h1 class="titulo-pagina">Cursos</h1>
        <p class="suave">Aprende algo que te dé ingresos mientras encuentras un empleo estable.</p>
      </div>
      <?= selectorEstado($estado, $tipo === 'emprender' ? ['tipo' => 'emprender'] : []) ?>
    </div>
    <div class="filtros filtros-solos">
      <a href="cursos.php" class="<?= $tipo === 'oficio' ? 'activo' : '' ?>">Aprender un oficio</a>
      <a href="cursos.php?tipo=emprender" class="<?= $tipo === 'emprender' ? 'activo' : '' ?>">Emprender</a>
    </div>

    <?php if (!$estado): ?>
      <p class="invitacion">Elige tu estado arriba para ver primero los cursos que tienes cerca.</p>
    <?php elseif ($estado === 'otro'): ?>
      <p class="invitacion">Por ahora tenemos directorios de Puebla, Veracruz, Oaxaca y Tlaxcala. Te mostramos los cursos nacionales y en línea.</p>
    <?php endif; ?>

    <?php if ($tipo === 'emprender'): ?>
      <p class="suave">Guías, trámites y apoyos para empezar un negocio propio: desde aprender a vender hasta conseguir equipo para trabajar.</p>
    <?php endif; ?>

    <div class="rejilla-3">
      <?php foreach ($cursos as $c): ?>
        <article class="tarjeta curso" id="curso-<?= $c['id'] ?>">
          <?php if ($c['estado']): ?><span class="sello sello-ok">En <?= e(ESTADOS[$c['estado']]) ?></span><?php endif; ?>
          <h3><?= e($c['nombre']) ?></h3>
          <p><?= e($c['descripcion']) ?></p>
          <p class="curso-datos"><?= e($c['modalidad']) ?> · <?= e($c['costo']) ?> · <?= $ritmos[$c['ritmo']] ?></p>
          <p class="institucion"><?= e($c['institucion']) ?></p>
          <p class="chico">Duración y fechas de inscripción: cambian según el plantel y el periodo; revísalas en el sitio oficial.</p>
          <div class="acciones-curso">
            <a class="boton linea chico-boton" href="<?= e($c['enlace']) ?>" target="_blank" rel="noopener">Sitio oficial</a>
            <?php if (esPersona()): ?>
              <form method="post">
                <?= campoToken() ?>
                <input type="hidden" name="curso" value="<?= $c['id'] ?>">
                <button class="boton chico-boton <?= in_array($c['id'], $guardados) ? 'guardado' : '' ?>" type="submit"><?= in_array($c['id'], $guardados) ? 'Lo quiero ✓' : 'Lo quiero' ?></button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if (!$cursos): ?>
      <div class="vacio"><h2>Aún no hay cursos cargados aquí</h2><p>Eso no te detiene: los cursos en línea están disponibles desde cualquier lugar.</p></div>
    <?php endif; ?>
    <p class="chico nota-legal"><b>Cuota simbólica:</b> aportación de bajo costo que piden los centros públicos de capacitación; el monto lo fija cada plantel. En las páginas de los CECATI puedes ver qué planteles ofrecen cada especialidad.</p>
  </div>
</section>
