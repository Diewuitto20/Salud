<?php
require 'includes/funciones.php';
soloPersonas();
$titulo = 'Cuestionario';

$preguntas = [
    'estres' => [
        'Sentir que no puedes controlar las cosas importantes de tu vida',
        'Sentir presión o agobio por tus gastos y pendientes',
        'Tensión en el cuerpo, irritabilidad o dolores de cabeza o cuello',
    ],
    'ansiedad' => [
        'Nervios, ansiedad o sentirte al límite',
        'No poder dejar de preocuparte por el futuro o por conseguir trabajo',
        'Dificultad para relajarte o para dormir por pensar en tus problemas',
    ],
    'autoestima' => [
        'Sentir que vales menos desde que perdiste tu empleo',
        'Sentirte un fracaso o que le has fallado a tu familia',
        'Dudar de tus capacidades para conseguir o hacer un buen trabajo',
    ],
    'familia' => [
        'Discusiones o tensión en casa por el dinero o por tu situación',
        'Sentir que tu familia no te entiende o no te apoya',
        'Alejarte de tu familia o amistades, o evitar convivir con ellos',
    ],
];
$opciones = ['Nunca', 'Varios días', 'Más de la mitad de los días', 'Casi todos los días'];
$meses = ['0-1' => 'Menos de 1 mes', '1-3' => '1 a 3 meses', '3-6' => '3 a 6 meses', '6-12' => '6 a 12 meses', '12+' => 'Más de un año'];
$impactos = ['Nada', 'Poco', 'Bastante', 'Mucho'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarToken();
    $puntos = [];
    foreach ($preguntas as $clave => $lista) {
        $puntos[$clave] = 0;
        foreach ($lista as $n => $p) {
            $puntos[$clave] += max(0, min(3, (int) ($_POST[$clave . $n] ?? 0)));
        }
    }
    $total = array_sum($puntos);
    $altos = count(array_filter($puntos, fn($p) => $p >= 7));
    $crisis = hayCrisis($_POST['relato'] ?? '');
    $casiDiario = 0;
    foreach ($preguntas as $clave => $lista) {
        foreach ($lista as $n => $p) {
            if ((int) ($_POST[$clave . $n] ?? 0) === 3) $casiDiario++;
        }
    }

    if ($crisis || $altos >= 2 || $total >= 24) $nivel = 'grave';
    elseif ($altos >= 1 || $total >= 15) $nivel = 'moderado';
    elseif ($total >= 8) $nivel = 'leve';
    else $nivel = 'minimo';

    $detectado = (int) ($_POST['animo_detectado'] ?? 0) ?: null;
    $confirmado = (int) ($_POST['animo_confirmado'] ?? 0) ?: null;
    $mes = array_key_exists($_POST['meses'] ?? '', $meses) ? $_POST['meses'] : '0-1';
    $impacto = max(0, min(3, (int) ($_POST['impacto'] ?? 0)));

    if (esPersona()) {
        bd()->prepare('INSERT INTO evaluaciones (usuario_id, meses, impacto, estres, ansiedad, autoestima, familia, total, nivel, animo_detectado, animo_confirmado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([usuario()['id'], $mes, $impacto, ...array_values($puntos), $total, $nivel, $detectado, $confirmado]);
    }
    $_SESSION['resultado'] = compact('puntos', 'total', 'nivel', 'crisis', 'impacto', 'confirmado', 'casiDiario');
    redirigir('resultado.php');
}
require 'includes/cabecera.php';
?>
<section class="seccion">
  <div class="contenedor angosto">
    <h1 class="titulo-pagina">¿Cómo te ha afectado quedarte sin trabajo?</h1>
    <p class="suave">Son doce preguntas sobre estrés, ansiedad, autoestima y vida familiar. Toma unos cuatro minutos. No es un diagnóstico: te ayuda a saber cómo estás y qué te puede servir ahora.</p>
    <p class="privacidad-cuestionario"><b>Solo tú ves tus respuestas.</b> Nos sirven para acompañarte mejor y nunca se comparten con empresas.</p>
    <div class="retomar" id="retomar" hidden>
      <p><b>Tienes un cuestionario a medias.</b> ¿Quieres continuar donde te quedaste?</p>
      <div class="acciones">
        <button type="button" class="boton chico-boton" data-continuar>Continuar donde me quedé</button>
        <button type="button" class="boton linea chico-boton" data-reiniciar>Empezar de nuevo</button>
      </div>
    </div>
    <?php if (!esPersona()): ?>
      <p class="invitacion">Puedes contestarlo sin cuenta. Si <a href="entrar.php?volver=cuestionario.php">inicias sesión</a>, guardamos tu resultado para que veas tu avance en «Mi ruta». Solo tú puedes verlo.</p>
    <?php endif; ?>

    <form method="post" class="cuestionario">
      <?= campoToken() ?>
      <div class="progreso" aria-live="polite">
        <div class="progreso-texto" id="progreso-texto"></div>
        <div class="progreso-barra"><i id="progreso-relleno"></i></div>
      </div>

      <div class="paso">
      <fieldset class="bloque">
        <legend>Con tus palabras <span class="opcional">(opcional)</span></legend>
        <p class="ayuda">Escribe o dicta cómo te has sentido. Al terminar de dictar te diremos qué ánimo notamos, para que lo confirmes.</p>
        <textarea id="relato" name="relato" rows="4" maxlength="1000" placeholder="Desde que me quedé sin trabajo…"></textarea>
        <div class="fila-voz">
          <button type="button" class="boton-voz" data-dictar="relato" data-animo><i></i><span>Dictar con voz</span></button>
          <span class="volumen" aria-hidden="true"><i></i></span>
        </div>
        <p class="provisional" aria-live="polite"></p>
        <div class="confirmar-animo" hidden>
          <p class="chico">Medidor de ánimo</p>
          <div class="medidor-animo">
            <?php foreach (['Muy mal', 'Mal', 'Más o menos', 'Bien', 'Muy bien'] as $i => $t): ?>
              <button type="button" data-n="<?= $i + 1 ?>"><?= $t ?></button>
            <?php endforeach; ?>
          </div>
          <p class="pregunta-animo"></p>
          <div class="acciones-animo">
            <button type="button" class="boton chico-boton" data-resp="si">Sí, así me siento</button>
            <button type="button" class="boton linea chico-boton" data-resp="no">No del todo</button>
          </div>
          <input type="hidden" name="animo_detectado">
          <input type="hidden" name="animo_confirmado">
        </div>
      </fieldset>
      </div>

      <div class="paso">
      <fieldset class="bloque">
        <legend>¿Cuánto tiempo llevas sin empleo?</legend>
        <div class="opciones">
          <?php foreach ($meses as $valor => $texto): ?>
            <label><input type="radio" name="meses" value="<?= $valor ?>" required><span><?= $texto ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      </div>

      <div class="paso">
      <fieldset class="bloque">
        <legend>¿Cuánto ha afectado tu estado de ánimo haber perdido tu trabajo?</legend>
        <div class="opciones">
          <?php foreach ($impactos as $valor => $texto): ?>
            <label><input type="radio" name="impacto" value="<?= $valor ?>" required><span><?= $texto ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      </div>

      <p class="separador">¿Con qué frecuencia te ha pasado esto en las últimas 2 semanas?</p>
      <?php foreach ($preguntas as $clave => $lista): ?>
        <h2 class="dimension"><?= DIMENSIONES[$clave] ?></h2>
        <?php foreach ($lista as $n => $p): ?>
          <div class="paso">
          <p class="seccion-paso"><?= DIMENSIONES[$clave] ?> · ¿Con qué frecuencia te ha pasado esto en las últimas 2 semanas?</p>
          <fieldset class="bloque pregunta-escala">
            <legend><?= e($p) ?></legend>
            <div class="opciones">
              <?php foreach ($opciones as $valor => $texto): ?>
                <label><input type="radio" name="<?= $clave . $n ?>" value="<?= $valor ?>" required><span><?= $texto ?></span></label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>

      <div class="navegacion-pasos" hidden>
        <button type="button" class="boton linea" data-atras>Atrás</button>
        <button type="button" class="boton" data-siguiente>Siguiente</button>
      </div>
      <p class="guardar-luego" hidden><button type="button" class="enlace" data-guardar>Guardar y seguir luego</button></p>
      <button class="boton grande" type="submit">Ver mi resultado</button>
      <p class="chico">Preguntas inspiradas en escalas usadas en psicología (estrés percibido, ansiedad generalizada y autoestima de Rosenberg) y adaptadas a la situación de desempleo. Si inicias sesión, tus respuestas se guardan según el <a href="privacidad.php">aviso de privacidad</a>.</p>
    </form>
  </div>
</section>
<?php require 'includes/pie.php'; ?>
