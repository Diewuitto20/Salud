<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor">
    <div class="encabezado">
      <div>
        <h1 class="titulo-pagina">Vacantes solidarias</h1>
        <p class="suave">Empresas y negocios que abrieron lugares para personas que perdieron su empleo.</p>
      </div>
      <?php if (!esEmpresa()): ?><?= selectorEstado($estado, array_filter(['modalidad' => $modalidad, 'q' => $buscar])) ?><?php endif; ?>
    </div>

    <?php if (!esEmpresa()): ?>
      <form method="get" class="filtros-vacantes">
        <input type="hidden" name="estado" value="<?= e($estado) ?>">
        <label class="oculto" for="q">Buscar por oficio o puesto</label>
        <input type="text" id="q" name="q" value="<?= e($buscar) ?>" placeholder="Buscar por oficio o puesto, ej. cocina">
        <select name="modalidad" aria-label="Modalidad">
          <option value="">Cualquier modalidad</option>
          <?php foreach (['Presencial', 'Remoto', 'Mixto'] as $m): ?><option <?= $modalidad === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
        </select>
        <button class="boton chico-boton" type="submit">Buscar</button>
      </form>

      <div class="avisarme">
        <p><b>¿No encuentras algo para ti?</b> Te avisamos cuando una empresa publique en tu estado.</p>
        <?php if (esPersona()): ?>
          <form method="post">
            <?= campoToken() ?>
            <input type="hidden" name="accion" value="avisos">
            <input type="hidden" name="activar" value="<?= $avisando ? 0 : 1 ?>">
            <button class="boton <?= $avisando ? 'linea' : '' ?> chico-boton" type="submit"><?= $avisando ? 'Dejar de avisarme' : 'Avísenme' ?></button>
          </form>
        <?php else: ?>
          <a class="boton chico-boton" href="entrar.php?volver=vacantes.php">Entra para recibir avisos</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="lista-tarjetas">
      <?php foreach ($vacantes as $v): ?>
        <article class="tarjeta vacante" id="vacante-<?= $v['id'] ?>">
          <div class="vacante-info">
            <h3><?= e($v['titulo']) ?></h3>
            <p class="curso-datos"><?= e($v['empresa']) ?> · <?= e($v['ubicacion']) ?><?= $v['estado'] ? ', ' . e(ESTADOS[$v['estado']] ?? '') : '' ?> · <?= e($v['modalidad']) ?></p>
            <?php if ($v['prestaciones']): ?>
              <span class="sello sello-ok">Con prestaciones de ley</span>
            <?php else: ?>
              <span class="sello sello-cuidado">Sin prestaciones de ley declaradas: pregunta antes de aceptar</span>
            <?php endif; ?>
            <?php if ($v['sueldo']): ?><p><b>Sueldo:</b> <?= e($v['sueldo']) ?></p><?php endif; ?>
            <p><?= nl2br(e($v['descripcion'])) ?></p>
          </div>
          <div class="vacante-accion">
            <p class="cupos <?= $v['libres'] > 0 ? '' : 'llenos' ?>">
              <b><?= max(0, $v['libres']) ?></b> de <?= $v['cupos'] ?> lugares libres
            </p>
            <?php if ($v['postulado']): ?>
              <span class="estado-ok">Ya te postulaste</span>
            <?php elseif ($v['libres'] < 1): ?>
              <span class="estado-lleno">Sin lugares disponibles</span>
            <?php elseif (esPersona()): ?>
              <details class="postular">
                <summary class="boton">Me postulo</summary>
                <form method="post" class="formulario">
                  <?= campoToken() ?>
                  <input type="hidden" name="vacante" value="<?= $v['id'] ?>">
                  <label>Teléfono o correo<input type="text" name="contacto" maxlength="120" required></label>
                  <label>Mensaje <span class="opcional">(opcional)</span><textarea name="mensaje" rows="2" maxlength="300" placeholder="Cuéntales por qué te interesa"></textarea></label>
                  <p class="chico">La empresa solo verá tu alias, este contacto y tu mensaje. Nunca tus resultados del cuestionario.</p>
                  <button class="boton" type="submit">Enviar postulación</button>
                </form>
              </details>
            <?php elseif (!usuario()): ?>
              <a class="boton" href="entrar.php?volver=vacantes.php">Entra para postularte</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$vacantes): ?>
        <div class="vacio">
          <h2>Aún no hay vacantes aquí</h2>
          <p>Eso no dice nada de lo que vales. Mientras llegan, puedes avanzar en un <a href="cursos.php">curso</a> o revisar los <a href="apoyos.php">apoyos</a> que te corresponden. Si activas «Avísenme», te diremos en cuanto aparezca una.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
