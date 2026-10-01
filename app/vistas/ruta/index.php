<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor">
    <p class="antetitulo">Mi ruta</p>
    <h1 class="titulo-pagina">Hola, <?= e(usuario()['nombre']) ?>.</h1>
    <p class="descargar-reporte"><a class="boton linea chico-boton" href="avance.php?formato=pdf">Descargar mi reporte PDF</a> <span class="chico">Tu bienestar, tu mapa de valía y tu avance en un documento privado.</span></p>

    <div class="retomar" id="retomar-ruta" hidden>
      <p><b>Dejaste un cuestionario a medias.</b> Puedes seguir donde te quedaste.</p>
      <a class="boton chico-boton" href="cuestionario.php">Continuar donde me quedé</a>
    </div>

    <?php if ($novedades): ?>
      <div class="novedades">
        <h2>Novedades desde tu última visita</h2>
        <?php foreach ($novedades as $n): ?><p><b><?= e($n['titulo']) ?></b> · <?= e($n['empresa']) ?> · <?= e($n['ubicacion']) ?></p><?php endforeach; ?>
        <a class="boton chico-boton" href="vacantes.php">Ver vacantes</a>
      </div>
    <?php endif; ?>

    <div class="siguiente-paso">
      <?php if (!$ultima): ?>
        <p><b>Tu primer paso:</b> contesta el cuestionario para saber cómo te está afectando esta etapa.</p>
        <a class="boton" href="cuestionario.php">Hacer el cuestionario</a>
      <?php elseif ($dias >= 14): ?>
        <p><b>Ya pasaron <?= $dias ?> días</b> desde tu último cuestionario. Repítelo para ver cómo vas.</p>
        <a class="boton" href="cuestionario.php">Repetir cuestionario</a>
      <?php else: ?>
        <p>Tu próximo cuestionario te toca en <b><?= 14 - $dias ?> días</b>. Mientras, sigue con tus cursos y tus postulaciones.</p>
        <a class="boton linea" href="cursos.php">Ver cursos</a>
      <?php endif; ?>
    </div>

    <div class="rejilla-avance">
      <article class="panel">
        <h2>Cómo te has sentido</h2>
        <?php if ($evaluaciones): ?>
          <p class="suave">Puntaje de cada cuestionario, de 0 a 36. Cuanto más bajo, mejor.</p>
          <div class="grafica"><?= graficaEvaluaciones(array_slice($evaluaciones, -8)) ?></div>
          <?php if (count($evaluaciones) > 1):
              $cambio = $primera['total'] - $ultima['total']; ?>
            <p class="tendencia <?= $cambio > 0 ? 'mejora' : ($cambio < 0 ? 'baja' : '') ?>">
              <?php if ($cambio > 0): ?>Desde tu primer cuestionario bajaste <b><?= $cambio ?> puntos</b>. Vas mejorando.
              <?php elseif ($cambio < 0): ?>Tu puntaje subió <b><?= -$cambio ?> puntos</b>. Está bien pedir ayuda: <a href="ayuda.php">mira dónde</a>.
              <?php else: ?>Tu puntaje se mantiene igual que en tu primer cuestionario.<?php endif; ?>
            </p>
          <?php endif; ?>
          <p class="chico">Último resultado: <b><?= nivelTexto($ultima['nivel']) ?></b> · <?= fecha($ultima['creado']) ?></p>
          <div class="chips">
            <?php foreach (DIMENSIONES as $clave => $nombre): ?>
              <span class="chip grado-<?= gradoDimension((int) $ultima[$clave]) ?>"><?= $nombre ?>: <?= ['bajo' => 'bajo', 'medio' => 'medio', 'alto' => 'alto'][gradoDimension((int) $ultima[$clave])] ?></span>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="suave">Aquí verás tu evolución cuando contestes el cuestionario.</p>
        <?php endif; ?>
      </article>

      <article class="panel">
        <h2>Mi plan de 30 días</h2>
        <?php if ($plan): [$cumplidasPlan, $totalPlan] = $avancePlan; ?>
          <p class="suave"><?= $diaPlan <= Plan::DIAS ? "Vas en el día <b>$diaPlan</b> de 30." : 'Terminaste tu plan. Arma uno nuevo.' ?></p>
          <div class="barra-avance"><span style="width: <?= $totalPlan ? round($cumplidasPlan / $totalPlan * 100) : 0 ?>%"></span></div>
          <p class="chico"><?= $cumplidasPlan ?> de <?= $totalPlan ?> metas cumplidas</p>
          <a class="boton chico-boton" href="plan.php#semana-actual">Ver mi semana</a>
        <?php else: ?>
          <p class="suave">Un calendario semanal hecho para ti: respirar, un curso, dos postulaciones y un trámite.</p>
          <a class="boton chico-boton" href="plan.php">Armar mi plan</a>
        <?php endif; ?>
      </article>

      <article class="panel">
        <h2>Mi mapa de valía</h2>
        <div class="cifras cifras-2">
          <div><b><?= $valia['logro'] ?></b><span>logros</span></div>
          <div><b><?= $valia['cualidad'] ?></b><span>cualidades</span></div>
        </div>
        <p class="suave">Lo que vales no depende de tener empleo. Anota hoy algo que hiciste bien.</p>
        <a class="boton linea chico-boton" href="valia.php">Abrir mi mapa</a>
      </article>

      <article class="panel" id="tramites">
        <h2>Mis trámites</h2>
        <?php foreach ($tramites as $clave => $a): ?>
          <div class="tramite">
            <div class="fila-entre">
              <a href="<?= e($a['enlace']) ?>" target="_blank" rel="noopener"><b><?= e($a['nombre']) ?></b></a>
              <form method="post"><?= campoToken() ?><input type="hidden" name="accion" value="quitar_tramite"><input type="hidden" name="apoyo" value="<?= $clave ?>"><button class="enlace" type="submit">Quitar</button></form>
            </div>
            <p class="chico">Ten a la mano: <?= e(implode(', ', $a['documentos'])) ?>.</p>
          </div>
        <?php endforeach; ?>
        <?php if (!$tramites): ?><p class="suave">Guarda apoyos con «Lo quiero» y aquí verás qué documentos necesitas.</p><?php endif; ?>
        <a class="boton linea chico-boton" href="apoyos.php">Ver apoyos</a>
      </article>

      <article class="panel">
        <h2>Tu comunidad</h2>
        <div class="cifras">
          <div><b><?= $comunidad['notas'] ?></b><span>notas publicadas</span></div>
          <div><b><?= $comunidad['apoyos'] ?></b><span>apoyos recibidos</span></div>
          <div><b><?= $comunidad['respuestas'] ?></b><span>respuestas</span></div>
        </div>
        <a class="boton linea chico-boton" href="foro.php">Ir al foro</a>
      </article>

      <article class="panel" id="cursos">
        <h2>Mis cursos</h2>
        <?php foreach ($cursos as $c): ?>
          <form method="post" class="fila-curso">
            <?= campoToken() ?>
            <input type="hidden" name="curso" value="<?= $c['id'] ?>">
            <div>
              <a href="<?= e($c['enlace']) ?>" target="_blank" rel="noopener"><b><?= e($c['nombre']) ?></b></a>
              <span class="chico"><?= e($c['institucion']) ?></span>
            </div>
            <select name="estado" onchange="this.form.submit()" aria-label="Estado del curso">
              <?php foreach ($estadosCurso as $estado): ?>
                <option <?= $c['estado'] === $estado ? 'selected' : '' ?>><?= $estado ?></option>
              <?php endforeach; ?>
              <option value="quitar">Quitar de mi lista</option>
            </select>
          </form>
        <?php endforeach; ?>
        <?php if (!$cursos): ?><p class="suave">Guarda cursos con el botón «Lo quiero» y lleva aquí tu avance.</p><?php endif; ?>
        <a class="boton linea chico-boton" href="cursos.php">Buscar cursos</a>
      </article>

      <article class="panel">
        <h2>Mis postulaciones</h2>
        <?php foreach ($postulaciones as $p): ?>
          <div class="fila-postulacion">
            <div><b><?= e($p['titulo']) ?></b><span class="chico"><?= e($p['empresa']) ?> · <?= fecha($p['creado']) ?></span></div>
            <span class="estado estado-<?= e(strtolower(str_replace([' ', 'ó'], ['-', 'o'], $p['estado']))) ?>"><?= e($p['estado']) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (array_filter($postulaciones, fn($p) => $p['estado'] === 'No seleccionado')): ?>
          <p class="contencion-suave">Un «no» de una empresa no habla de lo que vales: habla de lo que esa vacante buscaba hoy. Tu siguiente oportunidad sigue en camino.</p>
        <?php endif; ?>
        <?php if (!$postulaciones): ?><p class="suave">Todavía no te postulas a ninguna vacante.</p><?php endif; ?>
        <a class="boton linea chico-boton" href="vacantes.php">Ver vacantes</a>
      </article>

      <article class="panel" id="avisos">
        <h2>Mis avisos</h2>
        <p class="suave">Te avisamos aquí, en tu ruta, sin saturarte.</p>
        <form method="post" class="formulario">
          <?= campoToken() ?>
          <input type="hidden" name="accion" value="avisos">
          <label class="casilla"><input type="checkbox" name="vacantes" <?= $preferencias['avisar_vacantes'] ? 'checked' : '' ?>> <span>Vacantes nuevas en mi estado</span></label>
          <label class="casilla"><input type="checkbox" name="cursos" <?= $preferencias['avisar_cursos'] ? 'checked' : '' ?>> <span>Recordarme repetir el cuestionario cada 2 semanas</span></label>
          <button class="boton chico-boton" type="submit">Guardar</button>
        </form>
      </article>
    </div>
  </div>
</section>
