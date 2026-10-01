<?php
require 'includes/funciones.php';
require 'includes/apoyos_datos.php';
soloPersonas();
exigir('persona');
$titulo = 'Mi ruta';
$yo = usuario()['id'];
$estadosCurso = ['Me interesa', 'Inscrito', 'Terminado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarToken();
    if (($_POST['accion'] ?? '') === 'avisos') {
        bd()->prepare('INSERT INTO preferencias (usuario_id, avisar_vacantes, avisar_cursos) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE avisar_vacantes = VALUES(avisar_vacantes), avisar_cursos = VALUES(avisar_cursos)')
            ->execute([$yo, isset($_POST['vacantes']) ? 1 : 0, isset($_POST['cursos']) ? 1 : 0]);
        aviso('Guardamos tus preferencias de avisos.');
        redirigir('avance.php#avisos');
    }
    if (($_POST['accion'] ?? '') === 'quitar_tramite') {
        bd()->prepare('DELETE FROM apoyos_guardados WHERE usuario_id = ? AND apoyo = ?')->execute([$yo, $_POST['apoyo'] ?? '']);
        redirigir('avance.php#tramites');
    }
    $curso = (int) ($_POST['curso'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    if ($estado === 'quitar') {
        bd()->prepare('DELETE FROM cursos_guardados WHERE usuario_id = ? AND curso_id = ?')->execute([$yo, $curso]);
    } elseif (in_array($estado, $estadosCurso, true)) {
        bd()->prepare('UPDATE cursos_guardados SET estado = ? WHERE usuario_id = ? AND curso_id = ?')->execute([$estado, $yo, $curso]);
        if ($estado === 'Terminado') aviso('¡Felicidades por terminar tu curso! Es un paso enorme.');
    }
    redirigir('avance.php#cursos');
}

$st = bd()->prepare('SELECT * FROM evaluaciones WHERE usuario_id = ? AND total IS NOT NULL ORDER BY creado');
$st->execute([$yo]);
$evaluaciones = $st->fetchAll();
$ultima = end($evaluaciones) ?: null;
$primera = $evaluaciones[0] ?? null;
$dias = $ultima ? (int) floor((time() - strtotime($ultima['creado'])) / 86400) : null;

$st = bd()->prepare('SELECT p.estado, p.creado, v.titulo, u.nombre AS empresa FROM postulaciones p
    JOIN vacantes v ON v.id = p.vacante_id JOIN usuarios u ON u.id = v.empresa_id WHERE p.usuario_id = ? ORDER BY p.creado DESC');
$st->execute([$yo]);
$postulaciones = $st->fetchAll();

$st = bd()->prepare('SELECT g.estado, c.id, c.nombre, c.institucion, c.enlace FROM cursos_guardados g
    JOIN cursos c ON c.id = g.curso_id WHERE g.usuario_id = ? ORDER BY FIELD(g.estado, \'Inscrito\', \'Me interesa\', \'Terminado\'), c.nombre');
$st->execute([$yo]);
$cursos = $st->fetchAll();

$st = bd()->prepare('SELECT
    (SELECT COUNT(*) FROM notas WHERE usuario_id = ?) AS notas,
    (SELECT COUNT(*) FROM apoyos a JOIN notas n ON n.id = a.nota_id WHERE n.usuario_id = ?) AS apoyos,
    (SELECT COUNT(*) FROM comentarios c JOIN notas n ON n.id = c.nota_id WHERE n.usuario_id = ? AND c.usuario_id <> ?) AS respuestas');
$st->execute([$yo, $yo, $yo, $yo]);
$comunidad = $st->fetch();

$st = bd()->prepare('SELECT apoyo FROM apoyos_guardados WHERE usuario_id = ? ORDER BY creado');
$st->execute([$yo]);
$tramites = array_values(array_filter($st->fetchAll(PDO::FETCH_COLUMN), fn($a) => isset(APOYOS[$a])));

$st = bd()->prepare('SELECT tipo, COUNT(*) FROM valia WHERE usuario_id = ? GROUP BY tipo');
$st->execute([$yo]);
$valia = $st->fetchAll(PDO::FETCH_KEY_PAIR) + ['logro' => 0, 'cualidad' => 0];

$st = bd()->prepare('SELECT * FROM preferencias WHERE usuario_id = ?');
$st->execute([$yo]);
$preferencias = $st->fetch() ?: ['avisar_vacantes' => 0, 'avisar_cursos' => 0, 'ultima_visita' => null];
$estadoUsuario = estadoActual();
$novedades = [];
if ($preferencias['avisar_vacantes']) {
    $st = bd()->prepare("SELECT v.titulo, v.ubicacion, u.nombre AS empresa FROM vacantes v JOIN usuarios u ON u.id = v.empresa_id
        WHERE v.activa = 1 AND v.creado > ? AND (v.estado = ? OR v.modalidad = 'Remoto') ORDER BY v.creado DESC LIMIT 5");
    $st->execute([$preferencias['ultima_visita'] ?? '2000-01-01', $estadoUsuario]);
    $novedades = $st->fetchAll();
}
bd()->prepare('INSERT INTO preferencias (usuario_id, ultima_visita) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE ultima_visita = NOW()')->execute([$yo]);
require 'includes/cabecera.php';
?>
<section class="seccion">
  <div class="contenedor">
    <p class="antetitulo">Mi ruta</p>
    <h1 class="titulo-pagina">Hola, <?= e(usuario()['nombre']) ?>.</h1>

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
          <p class="suave">Puntaje de cada cuestionario, de 0 a 36. Entre más bajo, mejor.</p>
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
        <?php foreach ($tramites as $clave): $a = APOYOS[$clave]; ?>
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
<?php require 'includes/pie.php'; ?>
