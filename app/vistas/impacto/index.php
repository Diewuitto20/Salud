<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor">
    <p class="antetitulo">HackaTec 2026</p>
    <h1 class="titulo-pagina">Resultados del reto</h1>
    <p class="entrada">Cifras en vivo de ReActiva-T, agregadas y anónimas. Descarga el reporte en PDF para compartirlo con jueces o instituciones.</p>

    <?php if ($codigo !== '' && $reporte): ?>
      <div class="aviso aviso-ok"><b>Reporte auténtico.</b> El código <?= e(strtoupper($codigo)) ?> corresponde a un reporte generado en esta plataforma el <?= fecha($reporte['creado']) ?> de <?= date('Y', strtotime($reporte['creado'])) ?> a las <?= date('H:i', strtotime($reporte['creado'])) ?> h. Estas son las cifras que se guardaron en ese momento; compáralas con el PDF.</div>
    <?php elseif ($codigo !== ''): ?>
      <div class="aviso aviso-cuidado">No encontramos un reporte con el código <?= e($codigo) ?>. Revisa que esté bien escrito.</div>
    <?php endif; ?>

    <div class="siguiente-paso">
      <form method="get" class="verificar-reporte">
        <label for="codigo">¿Tienes un reporte en PDF? Escribe su código de verificación</label>
        <div><input id="codigo" name="codigo" maxlength="14" placeholder="XXXX-XXXX-XXXX" value="<?= e($codigo) ?>"> <button class="boton linea chico-boton" type="submit">Verificar</button></div>
      </form>
      <a class="boton" href="impacto.php?formato=pdf">Descargar reporte PDF</a>
    </div>

    <?php foreach (Impacto::secciones($c) as [$titulo, $datos]): ?>
      <h2 class="subtitulo"><?= $titulo ?></h2>
      <div class="cifras cifras-impacto">
        <?php foreach ($datos as [$valor, $etiqueta]): ?>
          <div><b><?= e((string) $valor) ?></b><span><?= e($etiqueta) ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <h2 class="subtitulo">Cómo están las personas hoy</h2>
    <p class="suave">Último cuestionario de cada persona.</p>
    <?php $filas = Impacto::filasNiveles($c); ?>
    <?php if (!$filas): ?>
      <p class="chico">Se publicará cuando al menos <?= Impacto::MINIMO ?> personas hayan contestado el cuestionario, para que nadie pueda ser identificado.</p>
    <?php endif; ?>
    <div class="barras-niveles">
      <?php foreach ($filas as [$nivel, $texto, $proporcion]): ?>
        <div class="fila-nivel">
          <span><?= nivelTexto($nivel) ?></span>
          <div class="pista"><i class="barra-<?= $nivel ?>" style="width: <?= round($proporcion * 100) ?>%"></i></div>
          <b><?= e($texto) ?></b>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
