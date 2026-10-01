<?php defined('RAIZ') or exit; ?>
</main>
<footer class="pie">
  <div class="contenedor">
    <p class="pie-seguridad"><b>Todos los trámites y apoyos son gratuitos.</b> No pagues a intermediarios ni des tus contraseñas a nadie.</p>
    <div class="pie-dentro">
      <span>¿Momento muy difícil? <a href="tel:8009112000"><b>Línea de la Vida 800 911 2000</b></a> · gratuita, 24 horas</span>
      <?php if (!esPersona()): ?><span><a class="enlace-pie" href="empresas.php">¿Tienes una vacante? Ayuda a alguien: publícala gratis</a></span><?php endif; ?>
      <span><a class="enlace-pie" href="privacidad.php">Aviso de privacidad</a> · <a class="enlace-pie" href="impacto.php">Resultados del reto</a> · ReActiva-T: reactiva tu vida y tu trabajo · HackaTec 2026</span>
    </div>
  </div>
</footer>
<dialog class="dialogo" id="confirmar-ayuda" aria-labelledby="titulo-ayuda">
  <h2 id="titulo-ayuda">¿Quieres ir a la página de ayuda?</h2>
  <p>Ahí encontrarás la Línea de la Vida (gratuita, 24 horas), ejercicios para calmarte y centros de atención cerca de ti.</p>
  <div class="acciones">
    <a class="boton urgente" href="ayuda.php">Sí, necesito ayuda</a>
    <button type="button" class="boton linea" data-cerrar>Cancelar</button>
  </div>
</dialog>
<dialog class="dialogo" id="aviso-externo" aria-labelledby="titulo-externo">
  <h2 id="titulo-externo">Vas a salir a una página oficial</h2>
  <p>Se abrirá en otra pestaña. ReActiva-T se queda aquí: regresa cuando quieras.</p>
  <div class="acciones">
    <a class="boton" id="ir-externo" href="#" target="_blank" rel="noopener">Continuar</a>
    <button type="button" class="boton linea" data-cerrar>Quedarme aquí</button>
  </div>
</dialog>
<script src="js/app.js"></script>
</body>
</html>
