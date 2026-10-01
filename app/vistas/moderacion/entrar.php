<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor angosto">
    <p class="antetitulo">Equipo ReActiva-T</p>
    <h1 class="titulo-pagina">Acceso de moderación</h1>
    <p class="suave">Esta sección es solo para el equipo. Escribe la clave de moderación.</p>
    <?php if ($error): ?><div class="aviso aviso-cuidado"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="formulario panel">
      <?= campoToken() ?>
      <input type="hidden" name="accion" value="entrar_moderacion">
      <label>Clave<input type="password" name="clave" required autocomplete="current-password"></label>
      <button class="boton" type="submit">Entrar</button>
    </form>
  </div>
</section>
