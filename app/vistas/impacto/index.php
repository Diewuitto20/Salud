<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor">
    <p class="antetitulo">HackaTec 2026</p>
    <h1 class="titulo-pagina">Resultados del reto</h1>
    <p class="entrada">Cifras en vivo de ReActiva-T, agregadas y anónimas. Descarga el reporte en PDF para compartirlo con jueces o instituciones.</p>

    <div class="siguiente-paso">
      <p>Huella de estas cifras: <b class="huella"><?= e($huella) ?></b><br><span class="chico">Si coincide con la del PDF, los datos no han cambiado desde que se generó.</span></p>
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
    <div class="barras-niveles">
      <?php $maximo = max(1, max($c['niveles']));
      foreach ($c['niveles'] as $nivel => $cuantos): ?>
        <div class="fila-nivel">
          <span><?= nivelTexto($nivel) ?></span>
          <div class="pista"><i class="barra-<?= $nivel ?>" style="width: <?= round($cuantos / $maximo * 100) ?>%"></i></div>
          <b><?= $cuantos ?> · <?= Impacto::porcentaje($cuantos, $c['evaluadas']) ?></b>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
