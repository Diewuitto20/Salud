<?php
require 'includes/funciones.php';
$titulo = 'Inicio';
$notas = bd()->query("SELECT n.texto, n.color, u.nombre FROM notas n JOIN usuarios u ON u.id = n.usuario_id
    WHERE u.tipo = 'persona' AND n.sensible = 0 AND n.oculta = 0 ORDER BY n.creado DESC LIMIT 3")->fetchAll();
$vacantes = (int) bd()->query('SELECT COALESCE(SUM(v.cupos), 0) - (SELECT COUNT(*) FROM postulaciones p JOIN vacantes x ON x.id = p.vacante_id WHERE x.activa = 1)
    FROM vacantes v WHERE v.activa = 1')->fetchColumn();
require 'includes/cabecera.php';
?>
<section class="portada">
  <div class="contenedor portada-dentro">
    <div>
      <p class="antetitulo">Reactiva tu vida y tu trabajo</p>
      <h1>Perdiste el empleo, no tu futuro.</h1>
      <p class="entrada">Aquí hay gente que está pasando por lo mismo, un cuestionario para saber cómo te está afectando, cursos para aprender un oficio y empresas que quieren darte una oportunidad.</p>
      <div class="acciones">
        <a class="boton" href="foro.php">Leer el foro</a>
        <a class="boton linea" href="cuestionario.php">Hacer el cuestionario</a>
      </div>
    </div>
    <div class="muro muro-portada">
      <?php foreach ($notas as $n): ?>
        <article class="nota nota-<?= e($n['color']) ?>">
          <p><?= e($n['texto']) ?></p>
          <span class="firma">— <?= e($n['nombre']) ?></span>
        </article>
      <?php endforeach; ?>
      <?php if (!$notas): ?>
        <article class="nota nota-amarillo">
          <p>Aquí aparecerán las notas de quienes están pasando por lo mismo. ¿Te animas a dejar la primera?</p>
          <a class="firma" href="foro.php">Ir al foro →</a>
        </article>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <h2>Qué encuentras aquí</h2>
    <div class="rejilla-4">
      <a class="tarjeta" href="foro.php"><span class="num">1</span><h3>Foro de apoyo</h3><p>Deja una nota o lee a otras personas que ya pasaron por esto.</p></a>
      <a class="tarjeta" href="cuestionario.php"><span class="num">2</span><h3>Cuestionario</h3><p>Diez preguntas para saber cómo te ha afectado el desempleo y si conviene pedir ayuda.</p></a>
      <a class="tarjeta" href="cursos.php"><span class="num">3</span><h3>Cursos</h3><p>Aprende un oficio o a emprender mientras llega el empleo que buscas.</p></a>
      <a class="tarjeta" href="vacantes.php"><span class="num">4</span><h3>Vacantes</h3><p><?php if ($vacantes > 0): ?>Hoy hay <b><?= $vacantes ?> lugares</b> disponibles en empresas que quieren ayudar.<?php else: ?>Empresas solidarias publican aquí los lugares que ofrecen.<?php endif; ?></p></a>
    </div>
  </div>
</section>

<section class="franja">
  <div class="contenedor franja-dentro">
    <div>
      <h2>¿Tienes una empresa o un negocio?</h2>
      <p>Publica vacantes para personas que perdieron su empleo. Tú decides cuántos lugares ofreces.</p>
    </div>
    <a class="boton claro" href="entrar.php?tipo=empresa">Ofrecer vacantes</a>
  </div>
</section>
<?php require 'includes/pie.php'; ?>
