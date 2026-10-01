<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor acceso">
    <div class="pestanas">
      <a href="<?= e($enlace(['modo' => 'entrar'])) ?>" class="<?= $modo === 'entrar' ? 'activo' : '' ?>">Iniciar sesión</a>
      <a href="<?= e($enlace(['modo' => 'registro'])) ?>" class="<?= $modo === 'registro' ? 'activo' : '' ?>">Crear cuenta</a>
    </div>

    <div class="panel">
      <?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>

      <?php if ($modo === 'entrar'): ?>
        <h1 class="titulo-panel">Qué gusto verte</h1>
        <form method="post" class="formulario">
          <?= campoToken() ?>
          <input type="hidden" name="modo" value="entrar">
          <label>Correo<input type="email" name="correo" required autocomplete="email" value="<?= e($previo['correo'] ?? '') ?>"></label>
          <label>Contraseña<input type="password" name="clave" required autocomplete="current-password"></label>
          <button class="boton" type="submit">Entrar</button>
        </form>
        <p class="chico centrado">¿No tienes cuenta? <a href="<?= e($enlace(['modo' => 'registro'])) ?>">Créala aquí</a></p>
      <?php else: ?>
        <h1 class="titulo-panel">Crea tu cuenta</h1>
        <p class="suave">Para quien perdió su empleo. Es gratis.</p>
        <form method="post" class="formulario">
          <?= campoToken() ?>
          <input type="hidden" name="modo" value="registro">
          <label>Alias
            <input type="text" name="nombre" minlength="3" maxlength="15" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 ]{3,15}" required placeholder="Ej. Lalo, Caro22" value="<?= e($previo['nombre'] ?? '') ?>">
            <span class="ayuda-campo">De 3 a 15 letras o números. Es lo único que se ve en el foro: tu nombre completo y tu correo nunca se muestran.</span>
          </label>
          <label>¿En qué estado vives?
            <select name="estado" required>
              <option value="">Elige tu estado</option>
              <?php foreach (ESTADOS as $clave => $nombreEstado): ?>
                <option value="<?= $clave ?>" <?= ($previo['estado'] ?? '') === $clave ? 'selected' : '' ?>><?= $nombreEstado ?></option>
              <?php endforeach; ?>
            </select>
            <span class="ayuda-campo">Así te mostramos cursos, apoyos y centros de atención cerca de ti.</span>
          </label>
          <label>Correo<input type="email" name="correo" required autocomplete="email" value="<?= e($previo['correo'] ?? '') ?>"></label>
          <label>Contraseña<input type="password" name="clave" minlength="6" required autocomplete="new-password"></label>
          <label class="casilla"><input type="checkbox" name="privacidad" required> <span>Leí y acepto el <a href="privacidad.php" target="_blank">aviso de privacidad</a>, incluido el uso de mis respuestas del cuestionario, que son datos sensibles.</span></label>
          <button class="boton" type="submit">Crear cuenta</button>
        </form>
      <?php endif; ?>
    </div>
    <p class="chico centrado">¿Eres empresa o negocio? <a href="empresas.php#acceso">Entra por aquí</a></p>
  </div>
</section>
