<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor angosto">
    <h1 class="titulo-pagina">Reiniciar datos</h1>
    <p class="entrada">Borra todas las cuentas, notas del foro, respuestas, cuestionarios, vacantes y postulaciones. Los cursos se conservan. No se puede deshacer.</p>
    <form method="post" class="acciones">
      <?= campoToken() ?>
      <button class="boton urgente" type="submit">Borrar todos los datos</button>
      <a class="boton linea" href="index.php">Cancelar</a>
    </form>
  </div>
</section>
