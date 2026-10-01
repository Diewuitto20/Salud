<?php defined('RAIZ') or exit;
$formularioPlan = function (string $boton) use ($estado, $intereses, $plan) { ?>
  <form method="post" class="formulario">
    <?= campoToken() ?>
    <input type="hidden" name="accion" value="crear">
    <label>¿En qué estado vives?
      <select name="estado" required>
        <option value="">Elige tu estado</option>
        <?php foreach (ESTADOS as $clave => $nombre): ?>
          <option value="<?= $clave ?>" <?= ($plan['estado'] ?? $estado) === $clave ? 'selected' : '' ?>><?= $nombre ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <fieldset class="opciones-plan">
      <legend>¿Qué te interesa más ahora?</legend>
      <?php foreach ($intereses as $clave => $texto): ?>
        <label class="casilla"><input type="radio" name="interes" value="<?= $clave ?>" required <?= ($plan['interes'] ?? '') === $clave ? 'checked' : '' ?>> <span><?= $texto ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <button class="boton" type="submit"><?= $boton ?></button>
  </form>
<?php };
$nombresMeta = ['respirar' => 'Respirar', 'curso' => 'Un curso', 'postulaciones' => 'Postulaciones', 'tramite' => 'Un trámite'];
?>
<section class="seccion">
  <div class="contenedor">
    <p class="antetitulo">Mi plan</p>
    <h1 class="titulo-pagina">Tu plan de 30 días</h1>
    <p class="entrada">Cada semana: respirar, avanzar en un curso, dos postulaciones y un trámite. Lo armamos con tu cuestionario, tu estado y lo que te interesa.</p>

    <?php if (!$ultima): ?>
      <div class="siguiente-paso">
        <p><b>Primero, el cuestionario.</b> Con tu resultado ajustamos cuánto pedirte cada semana.</p>
        <a class="boton" href="cuestionario.php">Hacer el cuestionario</a>
      </div>

    <?php elseif (!$plan): ?>
      <article class="panel panel-destacado plan-crear">
        <h2>Armemos tu plan</h2>
        <p class="suave">Tu último resultado: <b><?= nivelTexto($ultima['nivel']) ?></b> (<?= fecha($ultima['creado']) ?>).</p>
        <?php $formularioPlan('Armar mi plan'); ?>
      </article>

    <?php else:
      [$cumplidas, $totalMetas] = $avance;
      $porcentaje = $totalMetas ? round($cumplidas / $totalMetas * 100) : 0; ?>

      <div class="resumen-plan">
        <div>
          <p class="chico">Del <?= fecha($plan['inicio']) ?> al <?= fecha(date('Y-m-d', strtotime($plan['inicio'] . ' +29 days'))) ?> · <?= ESTADOS[$plan['estado']] ?? '' ?> · <?= $intereses[$plan['interes']] ?></p>
          <p class="dia-plan"><?= $semanaActual ? "Día <b>$dia</b> de 30" : '<b>Terminaste tus 30 días</b>' ?></p>
        </div>
        <div class="avance-plan">
          <p><b><?= $cumplidas ?></b> de <?= $totalMetas ?> metas cumplidas</p>
          <div class="barra-avance" role="progressbar" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= $porcentaje ?>%"></span></div>
        </div>
      </div>

      <?php if ($plan['nivel'] === 'grave' && $semanaActual === 1): ?>
        <div class="aviso aviso-cuidado">Antes que cualquier meta: tus respuestas muestran que estás pasando por un momento muy difícil. Habla hoy con alguien en la <a href="tel:8009112000"><b>Línea de la Vida 800 911 2000</b></a> o busca un <a href="ayuda.php">centro de atención cerca de ti</a>.</div>
      <?php endif; ?>

      <?php if ($resultadoNuevo || !$semanaActual): ?>
        <div class="siguiente-paso">
          <p><?= $semanaActual ? '<b>Tienes un resultado nuevo del cuestionario.</b> Puedes rehacer tu plan para que se ajuste a cómo estás hoy.' : '<b>¡Lo lograste!</b> Repite el cuestionario y arma un nuevo plan para los siguientes 30 días.' ?></p>
          <a class="boton chico-boton" href="#rehacer">Rehacer mi plan</a>
        </div>
      <?php endif; ?>

      <div class="calendario-plan">
        <?php foreach ($semanas as $n => $s):
          $todas = !array_filter($s['metas'], fn($m) => !$m['cumplida']);
          $etiqueta = $s['actual'] ? ['En curso', 'curso'] : ($todas ? ['Cumplida', 'cumplida'] : ($s['pasada'] ? ['Terminó', 'pasada'] : ['Próxima', 'proxima'])); ?>
          <article class="semana-plan <?= $s['actual'] ? 'semana-actual' : '' ?>"<?= $s['actual'] ? ' id="semana-actual"' : '' ?>>
            <header class="fila-entre">
              <h2>Semana <?= $n ?></h2>
              <span class="estado-semana estado-<?= $etiqueta[1] ?>"><?= $etiqueta[0] ?></span>
            </header>
            <p class="chico"><?= fecha($s['desde']) ?> – <?= fecha($s['hasta']) ?></p>
            <ul class="metas-plan">
              <?php foreach ($s['metas'] as $clave => $m): ?>
                <li class="meta-plan <?= $m['cumplida'] ? 'meta-cumplida' : '' ?>">
                  <span class="palomita" aria-hidden="true"><?= $m['cumplida'] ? '✓' : '' ?></span>
                  <div>
                    <p class="meta-tipo"><?= $nombresMeta[$clave] ?><?= $m['meta'] > 1 ? " · {$m['hecho']}/{$m['meta']}" : '' ?></p>
                    <p><b><?= e($m['titulo']) ?></b></p>
                    <p class="chico"><?= e($m['detalle']) ?></p>
                    <div class="acciones-meta">
                      <a href="<?= e($m['enlace']) ?>"<?= !empty($m['externo']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $m['textoEnlace'] ?></a>
                      <?php if ($m['marcable']): ?>
                        <form method="post">
                          <?= campoToken() ?>
                          <input type="hidden" name="accion" value="marcar">
                          <input type="hidden" name="meta" value="<?= $clave ?>">
                          <?php if ($clave === 'respirar'): ?>
                            <button class="boton chico-boton <?= $m['marcadaHoy'] ? 'guardado' : 'linea' ?>" type="submit"><?= $m['marcadaHoy'] ? 'Hoy ya respiré ✓' : 'Hoy respiré' ?></button>
                          <?php else: ?>
                            <button class="boton chico-boton <?= $m['cumplida'] ? 'guardado' : 'linea' ?>" type="submit"><?= $m['cumplida'] ? 'Hecho ✓' : 'Marcar como hecho' ?></button>
                          <?php endif; ?>
                        </form>
                      <?php elseif ($m['auto']): ?>
                        <span class="chico">Se marca solo</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      </div>

      <details class="panel rehacer" id="rehacer"<?= $resultadoNuevo || !$semanaActual ? ' open' : '' ?>>
        <summary><b>Rehacer mi plan</b> <span class="chico">· empieza de nuevo hoy con tu último resultado (<?= nivelTexto($ultima['nivel']) ?>)</span></summary>
        <?php $formularioPlan('Rehacer mi plan'); ?>
      </details>
    <?php endif; ?>
  </div>
</section>
