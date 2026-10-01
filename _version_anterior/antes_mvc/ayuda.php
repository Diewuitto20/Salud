<?php
require 'includes/funciones.php';
$titulo = 'Ayuda';
$estado = estadoActual();
$centros = [
    'puebla' => [
        ['Puebla Norte', 'Diagonal de la 88 Pte. y 9 Nte., Col. Infonavit San Pedro', '2222884431'],
        ['Puebla Sur', 'Antiguo Camino a Guadalupe Hidalgo 11350, Col. Agua Santa', '2223996588'],
        ['San Pedro Cholula', '17 Nte. 202, esq. Vicente Guerrero, Col. San Cristóbal Tepontla', '2222617327'],
        ['San Martín Texmelucan', 'Camino al Moral s/n, Col. El Moral', '2481122493'],
        ['Tehuacán', '19 Pte. 3800, Col. México en la Exhacienda El Riego', '2381071832'],
        ['Tepeaca', 'Blvd. Dr. Antonio López Rosas s/n, Col. San Isidro', '2234782099'],
        ['Chalchicomula de Sesma', '3 Nte. 1, Col. Centro', '2454524011'],
        ['Libres', '14 Sur 1104, Barrio Tetela', '2764732034'],
    ],
    'oaxaca' => [
        ['Santa Cruz Xoxocotlán', 'Hornos y Progreso s/n, Col. Centro', '9515172448'],
        ['Trinidad de Viguera', 'Av. Los Higos s/n', '9515204501'],
        ['Huajuapan', 'Vicente Suárez Lt. 1, 2 y 3, Col. El Rosario', '9535552320'],
        ['Tuxtepec', 'Carretera a Loma Alta s/n, Col. El Bosque', '2878748790'],
        ['Puerto Escondido', 'Raúl González s/n, Col. Jardines', '9541150300'],
        ['Pinotepa Nacional', '2a Pte. s/n esq. 15 Sur, Col. Aviación', '9545432916'],
        ['Tehuantepec', 'Av. Universidad s/n, Barrio Santa Cruz Tagolaba', '9717136202'],
    ],
];
$enlacesEstado = [
    'veracruz' => ['Centros de salud mental (UNEME-CECOSAMA) de Veracruz', 'https://www.ssaver.gob.mx/ceca/uneme-cecosama-centros-comunitarios-de-salud-mental-y-adicciones/'],
    'tlaxcala' => ['Centros de salud mental y adicciones de Tlaxcala', 'https://www.tlaxcala.gob.mx/index.php/tramites-y-servicios/salud'],
];
require 'includes/cabecera.php';
?>
<section class="crisis">
  <div class="contenedor angosto">
    <p class="antetitulo">No estás solo</p>
    <h1 class="titulo-pagina">Lo que sientes importa, y hay gente lista para escucharte ahora.</h1>
    <p class="entrada">No tienes que decidir nada en este momento. Elige una sola cosa de esta página.</p>

    <div class="caja-ayuda">
      <p><b>Línea de la Vida</b> · gratuita, confidencial, las 24 horas, los 365 días</p>
      <a class="telefono" href="tel:8009112000">800 911 2000</a>
      <div class="acciones">
        <a class="boton urgente" href="tel:8009112000">Llamar ahora</a>
        <a class="boton linea" href="tel:911">Emergencias 911</a>
        <a class="boton linea" href="https://wa.me/?text=%C2%BFPuedes%20hablar%20conmigo%20un%20rato%3F%20No%20me%20siento%20bien." target="_blank" rel="noopener">Escribirle a alguien de confianza</a>
      </div>
    </div>

    <div class="respiracion">
      <h2>Respira conmigo un minuto</h2>
      <div class="circulo-respirar" aria-hidden="true"><div class="bola-respirar" id="bola-respirar"></div></div>
      <p class="indicacion-respirar" id="indicacion-respirar" aria-live="polite">Toma aire en 4 tiempos y suéltalo en 6.</p>
      <button class="boton" type="button" id="iniciar-respiracion">Empezar</button>
    </div>

    <h2>Centros de salud mental cerca de ti</h2>
    <p class="suave">Los Centros Comunitarios de Salud Mental y Adicciones (CECOSAMA) atienden gratis o a bajo costo.</p>
    <?= selectorEstado($estado) ?>
    <?php if (isset($centros[$estado])): ?>
      <ul class="directorio">
        <?php foreach ($centros[$estado] as [$nombre, $direccion, $telefono]): ?>
          <li><b><?= e($nombre) ?></b><span><?= e($direccion) ?></span><a href="tel:<?= $telefono ?>"><?= e(preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1 $2 $3', $telefono)) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <p class="chico">Fuente: Directorio Nacional de UNEME-CECOSAMA, CONASAMA (mayo de 2026). Llama antes de ir para confirmar horarios.</p>
    <?php elseif (isset($enlacesEstado[$estado])): ?>
      <p><a class="boton linea" href="<?= e($enlacesEstado[$estado][1]) ?>" target="_blank" rel="noopener"><?= e($enlacesEstado[$estado][0]) ?></a></p>
    <?php else: ?>
      <p><a class="boton linea" href="https://www.conasama.salud.gob.mx/directorio/Directorio_CECOSAMA_260526.pdf" target="_blank" rel="noopener">Ver el directorio nacional de CECOSAMA</a></p>
    <?php endif; ?>

    <h2>Otras opciones</h2>
    <ul class="lista">
      <li><b>Centros de Integración Juvenil (CIJ).</b> Atención en salud mental con presencia en todo el país.</li>
      <li><b>Tu centro de salud, IMSS, ISSSTE o IMSS-Bienestar.</b> Pide consulta de psicología.</li>
      <li><b>El DIF de tu municipio.</b> Suele ofrecer orientación psicológica y familiar.</li>
    </ul>
  </div>
</section>
<?php require 'includes/pie.php'; ?>
