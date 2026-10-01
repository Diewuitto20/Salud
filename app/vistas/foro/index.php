<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor">
    <div class="encabezado">
      <div>
        <h1 class="titulo-pagina">El muro</h1>
        <p class="suave">Notas de personas que perdieron su empleo. Lee, apoya y deja una para alguien que la necesite.</p>
      </div>
      <div class="filtros">
        <a href="foro.php" class="<?= $orden === 'apoyadas' ? '' : 'activo' ?>">Recientes</a>
        <a href="foro.php?orden=apoyadas" class="<?= $orden === 'apoyadas' ? 'activo' : '' ?>">Más apoyadas</a>
      </div>
    </div>

    <div class="normas">
      <p><b>Aquí compartimos con respeto.</b> Escribe como te gustaría que te hablaran: sin burlas, sin groserías y sin pedir dinero. Si algo de lo que lees te afecta, toca <a href="ayuda.php" class="ayuda-ya">Necesito ayuda</a>.</p>
      <?php if (!esEmpresa()): ?><a class="boton linea chico-boton" href="cuestionario.php">¿Cómo te sientes hoy? Hacer el cuestionario</a><?php endif; ?>
    </div>

    <?php if (usuario()): ?>
      <form method="post" class="nueva-nota nota nota-amarillo" id="nueva-nota">
        <?= campoToken() ?>
        <input type="hidden" name="accion" value="nota">
        <label for="texto-nota" class="oculto">Tu nota</label>
        <textarea id="texto-nota" name="texto" maxlength="280" required placeholder="Escribe algo que te hubiera gustado leer cuando perdiste tu empleo…"><?= e($borrador) ?></textarea>
        <p class="guia-nota">Palabras breves ayudan mucho. Solo se muestra tu alias, nunca tu nombre completo ni tu correo.</p>
        <label class="casilla-nota"><input type="checkbox" name="sensible"> Mi nota habla de un tema difícil (se mostrará con advertencia)</label>
        <div class="nueva-nota-pie">
          <div class="colores" role="radiogroup" aria-label="Color de la nota">
            <?php foreach ($colores as $i => $c): ?>
              <label><input type="radio" name="color" value="<?= $c ?>" <?= $i === 0 ? 'checked' : '' ?>><span class="muestra nota-<?= $c ?>" title="<?= ucfirst($c) ?>"></span></label>
            <?php endforeach; ?>
          </div>
          <button type="button" class="boton-voz" data-dictar="texto-nota"><i></i><span>Dictar</span></button>
          <button class="boton" type="submit">Pegar nota</button>
        </div>
      </form>
    <?php else: ?>
      <p class="invitacion"><a href="entrar.php?volver=foro.php">Inicia sesión</a> para dejar una nota o apoyar a alguien.</p>
    <?php endif; ?>

    <div class="muro">
      <?php foreach ($notas as $n): ?>
        <article class="nota nota-<?= e($n['color']) ?>" id="nota-<?= $n['id'] ?>">
          <?php if ($n['tipo'] === 'empresa'): ?><span class="etiqueta">Empresa</span><?php endif; ?>
          <?php if ($n['sensible']): ?>
            <details class="contenido-sensible">
              <summary><b>Advertencia de contenido.</b> Esta nota habla de temas que pueden ser difíciles de leer. <span>Mostrar nota</span></summary>
              <p><?= nl2br(e($n['texto'])) ?></p>
            </details>
          <?php else: ?>
            <p><?= nl2br(e($n['texto'])) ?></p>
          <?php endif; ?>
          <span class="firma">— <?= e($n['nombre']) ?> · <?= hace($n['creado']) ?></span>
          <div class="nota-acciones">
            <form method="post">
              <?= campoToken() ?>
              <input type="hidden" name="accion" value="apoyo">
              <input type="hidden" name="nota" value="<?= $n['id'] ?>">
              <button class="apoyo <?= $n['mio'] ? 'dado' : '' ?>" type="submit" <?= usuario() ? '' : 'disabled' ?>>Te apoyo · <?= $n['apoyos'] ?></button>
            </form>
            <?php if (usuario() && (int) $n['usuario_id'] !== usuario()['id']): ?>
              <form method="post" class="form-denunciar">
                <?= campoToken() ?>
                <input type="hidden" name="accion" value="denunciar">
                <input type="hidden" name="nota" value="<?= $n['id'] ?>">
                <button class="denunciar" type="submit" <?= $n['denunciada'] ? 'disabled' : '' ?> title="Reportar mensaje inapropiado"><?= $n['denunciada'] ? 'Reportada' : 'Reportar' ?></button>
              </form>
            <?php endif; ?>
            <details>
              <summary>Responder (<?= count($comentarios[$n['id']] ?? []) ?>)</summary>
              <?php foreach ($comentarios[$n['id']] ?? [] as $c): ?>
                <p class="comentario"><b><?= e($c['nombre']) ?>:</b> <?= e($c['texto']) ?></p>
              <?php endforeach; ?>
              <?php if (usuario()): ?>
                <form method="post" class="responder">
                  <?= campoToken() ?>
                  <input type="hidden" name="accion" value="comentario">
                  <input type="hidden" name="nota" value="<?= $n['id'] ?>">
                  <input type="text" name="texto" maxlength="280" required placeholder="Unas palabras de ánimo…">
                  <button type="submit">Enviar</button>
                </form>
              <?php endif; ?>
            </details>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if (!$notas): ?><p class="suave">Todavía no hay notas. La primera puede ser la tuya.</p><?php endif; ?>
  </div>
</section>
