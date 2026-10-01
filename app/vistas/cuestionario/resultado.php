<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor angosto">
    <p class="antetitulo">Tu resultado</p>
    <h1 class="titulo-pagina"><?= $encabezado ?></h1>
    <p class="entrada"><?= $mensaje ?></p>

    <div class="medidas">
      <?php foreach (DIMENSIONES as $clave => $nombre):
          $p = $r['puntos'][$clave];
          $grado = gradoDimension($p); ?>
        <div>
          <span><?= $nombre ?></span>
          <div class="barra-medida"><i class="grado-<?= $grado ?>" style="width: <?= max(4, round($p / 9 * 100)) ?>%"></i></div>
          <small><?= ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'][$grado] ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (array_filter($r['puntos'], fn($p) => $p >= 4)): ?>
      <div class="consejos">
        <?php if ($r['puntos']['estres'] >= 4): ?>
          <p><b>Estrés:</b> divide tus pendientes en pasos pequeños y revisa los <a href="apoyos.php">apoyos del gobierno</a> que pueden aliviar tus gastos mientras encuentras trabajo.</p>
        <?php endif; ?>
        <?php if ($r['puntos']['ansiedad'] >= 4): ?>
          <p><b>Ansiedad:</b> respira despacio (4 tiempos al inhalar, 6 al soltar) y ponle horario a la búsqueda de empleo para que no ocupe todo tu día.</p>
        <?php endif; ?>
        <?php if ($r['puntos']['autoestima'] >= 4): ?>
          <p><b>Autoestima:</b> perder un empleo no dice cuánto vales. Lee a otras personas en el <a href="foro.php">foro</a> y date logros pequeños, como avanzar en un curso.</p>
        <?php endif; ?>
        <?php if ($r['puntos']['familia'] >= 4): ?>
          <p><b>Vida familiar:</b> platicar con calma sobre la situación ayuda más que cargarla en silencio. El DIF de tu municipio suele ofrecer orientación familiar.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($r['confirmado']): ?>
      <p class="chico">También nos dijiste que hoy te sientes <b><?= $nombresAnimo[$r['confirmado']] ?></b>.</p>
    <?php endif; ?>

    <?php if ($r['crisis']): ?>
      <div class="caja-ayuda">
        <p><b>Lo que escribiste nos importa.</b> Habla ahora con alguien preparado para escucharte. Es gratis y atienden las 24 horas.</p>
        <a class="telefono" href="tel:8009112000">800 911 2000</a>
        <div class="acciones">
          <a class="boton urgente" href="tel:8009112000">Llamar a la Línea de la Vida</a>
          <a class="boton linea" href="ayuda.php">Más opciones de ayuda</a>
        </div>
      </div>
    <?php elseif (($r['casiDiario'] ?? 0) >= 4 || $r['nivel'] === 'grave'): ?>
      <div class="caja-ayuda contencion">
        <h2>Lo que estás viviendo es mucho para cargarlo a solas</h2>
        <p>Varias de tus respuestas dicen que esto te pasa casi todos los días. No es exagerar ni ser débil: es una señal de que mereces apoyo ahora, no después.</p>
        <div class="acciones">
          <a class="boton urgente" href="ayuda.php">Ir a la página de ayuda</a>
          <a class="boton linea" href="tel:8009112000">Llamar a la Línea de la Vida 800 911 2000</a>
        </div>
        <p class="chico">También te recomendamos hablar con un profesional. En la página de ayuda verás los centros de atención gratuitos de tu estado.</p>
      </div>
    <?php elseif (in_array($r['nivel'], ['grave', 'moderado'], true)): ?>
      <div class="caja-profesional">
        <h2>Te recomendamos hablar con un profesional</h2>
        <p>Pedir ayuda no es exagerar: es cuidarte. Hay opciones gratuitas o de bajo costo.</p>
        <div class="acciones">
          <a class="boton" href="ayuda.php">Dónde encontrar ayuda</a>
          <a class="boton linea" href="tel:8009112000">Línea de la Vida 800 911 2000</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!$r['crisis']): ?>
      <a class="banda-apoyos" href="apoyos.php">
        <b>Apoyos del gobierno para quien perdió su empleo</b>
        <span>Atención médica del IMSS, becas de capacitación, retiro por desempleo de tu AFORE y más →</span>
      </a>

      <h2 class="subtitulo">Aprende un oficio mientras llega el empleo</h2>
      <p class="suave"><?= $ritmo === 0 ? 'Elegimos cursos cortos y flexibles, para no exigirte de más ahora.' : ($ritmo === 1 ? 'Estos cursos tienen un ritmo moderado.' : 'Tienes energía para algo más completo y con constancia.') ?></p>
      <div class="rejilla-3">
        <?php foreach ($oficios as $c): ?>
          <article class="tarjeta curso">
            <h3><?= e($c['nombre']) ?></h3>
            <p><?= e($c['descripcion']) ?></p>
            <p class="curso-datos"><?= e($c['modalidad']) ?> · <?= e($c['costo']) ?> · <?= $ritmos[$c['ritmo']] ?></p>
            <p class="institucion"><?= e($c['institucion']) ?></p>
            <a class="boton linea chico-boton" href="<?= e($c['enlace']) ?>" target="_blank" rel="noopener">Ver en el sitio oficial</a>
          </article>
        <?php endforeach; ?>
      </div>

      <h2 class="subtitulo">O empieza algo propio</h2>
      <div class="rejilla-3">
        <?php foreach ($emprender as $c): ?>
          <article class="tarjeta curso">
            <h3><?= e($c['nombre']) ?></h3>
            <p><?= e($c['descripcion']) ?></p>
            <p class="curso-datos"><?= e($c['modalidad']) ?> · <?= e($c['costo']) ?></p>
            <p class="institucion"><?= e($c['institucion']) ?></p>
            <a class="boton linea chico-boton" href="<?= e($c['enlace']) ?>" target="_blank" rel="noopener">Ver en el sitio oficial</a>
          </article>
        <?php endforeach; ?>
        <a class="tarjeta tarjeta-mas" href="cursos.php">Ver todos los cursos →</a>
      </div>

      <?php if ($vacantes): ?>
        <h2 class="subtitulo">Empresas que hoy tienen lugares</h2>
        <ul class="lista-vacantes">
          <?php foreach ($vacantes as $v): ?>
            <li><b><?= e($v['titulo']) ?></b> · <?= e($v['empresa']) ?> · <?= e($v['ubicacion']) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="boton linea" href="vacantes.php">Ver vacantes</a>
      <?php endif; ?>
    <?php endif; ?>

    <p class="chico nota-legal">Este resultado es orientativo y no sustituye la valoración de un profesional de la salud.</p>
  </div>
</section>
