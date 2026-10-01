<?php
require 'includes/funciones.php';
$titulo = 'Reiniciar datos';
if (!esLocal()) {
    http_response_code(403);
    exit('Esta página solo se puede abrir desde la computadora donde corre el servidor.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarToken();
    $pdo = bd();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['preferencias', 'apoyos_guardados', 'valia', 'denuncias', 'cursos_guardados', 'postulaciones', 'vacantes', 'evaluaciones', 'comentarios', 'apoyos', 'notas', 'usuarios'] as $tabla) {
        $pdo->exec("TRUNCATE TABLE $tabla");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    session_destroy();
    session_start();
    aviso('Listo. Se borraron cuentas, notas, cuestionarios y vacantes. El catálogo de cursos se conserva.');
    redirigir('index.php');
}
require 'includes/cabecera.php';
?>
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
<?php require 'includes/pie.php'; ?>
