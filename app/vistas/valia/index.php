<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor">
    <p class="antetitulo">Espacio privado · solo tú lo ves</p>
    <h1 class="titulo-pagina">Mi mapa de valía</h1>
    <p class="entrada">Perder un empleo no borra lo que sabes hacer ni lo que has logrado. Anótalo aquí y vuelve cuando lo necesites recordar.</p>

    <div class="dos-col">
      <form method="post" class="panel formulario">
        <?= campoToken() ?>
        <input type="hidden" name="accion" value="agregar">
        <h2>Agregar a mi mapa</h2>
        <div class="pestanas pestanas-radio" role="radiogroup" aria-label="Tipo">
          <label><input type="radio" name="tipo" value="logro" checked><span>Un logro</span></label>
          <label><input type="radio" name="tipo" value="cualidad"><span>Una cualidad</span></label>
        </div>
        <label for="texto-valia">¿Qué quieres recordar?</label>
        <textarea id="texto-valia" name="texto" rows="3" maxlength="200" required placeholder="Ej. Terminé mi primer módulo del curso de electricidad"></textarea>
        <div class="chips-sugerencias" aria-label="Ideas de cualidades">
          <?php foreach ($sugerencias as $s): ?><button type="button" class="chip-sugerencia" data-sugerencia="<?= e($s) ?>"><?= e($s) ?></button><?php endforeach; ?>
        </div>
        <button class="boton" type="submit">Guardar</button>
      </form>

      <div class="mapa">
        <div class="columna-valia">
          <h2>Mis logros <span class="contador"><?= count($items['logro']) ?></span></h2>
          <?php foreach ($items['logro'] as $i): ?>
            <div class="valia-item logro"><p><?= e($i['texto']) ?></p><span class="chico"><?= fecha($i['creado']) ?></span>
              <form method="post"><?= campoToken() ?><input type="hidden" name="accion" value="borrar"><input type="hidden" name="id" value="<?= $i['id'] ?>"><button class="enlace" type="submit" aria-label="Quitar">Quitar</button></form>
            </div>
          <?php endforeach; ?>
          <?php if (!$items['logro']): ?><p class="suave">Todo cuenta: un trámite que terminaste, una entrevista a la que fuiste, un día en que no te rendiste.</p><?php endif; ?>
        </div>
        <div class="columna-valia">
          <h2>Mis cualidades <span class="contador"><?= count($items['cualidad']) ?></span></h2>
          <?php foreach ($items['cualidad'] as $i): ?>
            <div class="valia-item cualidad"><p><?= e($i['texto']) ?></p>
              <form method="post"><?= campoToken() ?><input type="hidden" name="accion" value="borrar"><input type="hidden" name="id" value="<?= $i['id'] ?>"><button class="enlace" type="submit" aria-label="Quitar">Quitar</button></form>
            </div>
          <?php endforeach; ?>
          <?php if (!$items['cualidad']): ?><p class="suave">Lo que la gente valora de ti, en el trabajo o en tu casa.</p><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
