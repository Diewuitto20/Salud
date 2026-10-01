<?php defined('RAIZ') or exit; ?>
<section class="seccion">
  <div class="contenedor">
    <h1 class="titulo-pagina"><?= e(usuario()['nombre']) ?></h1>
    <p class="suave">Publica lugares para personas que perdieron su empleo y revisa quién se postuló.</p>

    <div class="dos-col empresa">
      <form method="post" class="panel formulario" id="form-vacante">
        <h2>Publicar vacante</h2>
        <p class="chico">Los campos con <span class="obligatorio">*</span> son obligatorios.</p>
        <?= campoToken() ?>
        <input type="hidden" name="accion" value="publicar">
        <label><span>Puesto <span class="obligatorio">*</span></span><input type="text" name="titulo" maxlength="100" required placeholder="Ej. Ayudante de cocina"></label>
        <label><span>Descripción <span class="obligatorio">*</span></span><textarea name="descripcion" rows="4" required placeholder="Qué hará la persona, horario, si necesitas experiencia…"></textarea></label>
        <div class="fila-campos">
          <label><span>Estado <span class="obligatorio">*</span></span>
            <select name="estado" required>
              <option value="">Elige…</option>
              <?php foreach (ESTADOS as $clave => $nombreEstado): ?><option value="<?= $clave ?>"><?= $nombreEstado ?></option><?php endforeach; ?>
            </select>
          </label>
          <label><span>Ciudad <span class="obligatorio">*</span></span><input type="text" name="ubicacion" maxlength="100" required placeholder="Ej. Apizaco"></label>
        </div>
        <div class="fila-campos">
          <label><span>Sueldo <span class="obligatorio">*</span></span>
            <span class="campo-moneda"><span aria-hidden="true">$</span><input type="text" name="sueldo" id="sueldo" inputmode="numeric" maxlength="7" required placeholder="2,500" autocomplete="off"></span>
          </label>
          <label><span>Periodo <span class="obligatorio">*</span></span>
            <select name="periodo" required>
              <?php foreach ($periodos as $periodo): ?><option <?= $periodo === 'a la semana' ? 'selected' : '' ?>><?= $periodo ?></option><?php endforeach; ?>
            </select>
          </label>
        </div>
        <label class="casilla"><input type="checkbox" name="prestaciones" id="prestaciones" checked> <span><b>Ofrece prestaciones de ley:</b> alta en el IMSS, aguinaldo, vacaciones y sueldo de al menos el salario mínimo.</span></label>
        <p class="aviso-campo" id="sin-prestaciones" hidden>Recomendamos ofrecer prestaciones para atraer a más personas.</p>
        <label><span>Modalidad <span class="obligatorio">*</span></span>
            <select name="modalidad" required><option>Presencial</option><option>Remoto</option><option>Mixto</option></select>
          </label>
          <label><span>Número de vacantes disponibles <span class="obligatorio">*</span></span><input type="text" name="cupos" id="cupos" value="1" required inputmode="numeric" maxlength="2" pattern="([1-9]|[1-4][0-9]|50)" title="Escribe un número del 1 al 50" autocomplete="off"><span class="opcional">De 1 a 50</span></label>
        <button class="boton" type="submit">Publicar</button>
      </form>
      <dialog class="dialogo" id="confirmar-vacante" aria-labelledby="titulo-vacante">
        <h2 id="titulo-vacante">Vas a publicar esta vacante</h2>
        <p id="resumen-vacante"></p>
        <p>¿Revisaste que incluya trato digno y prestaciones?</p>
        <p class="aviso-campo" id="dialogo-sin-prestaciones" hidden>No marcaste prestaciones de ley: la vacante aparecerá con el aviso «Sin prestaciones de ley declaradas».</p>
        <div class="acciones">
          <button type="button" class="boton" id="publicar-vacante">Sí, publicar</button>
          <button type="button" class="boton linea" data-cerrar>Revisar</button>
        </div>
      </dialog>

      <div>
        <h2>Tus vacantes</h2>
        <?php foreach ($vacantes as $v): ?>
          <article class="tarjeta vacante-propia <?= $v['activa'] ? '' : 'cerrada' ?>">
            <div class="fila-entre">
              <h3><?= e($v['titulo']) ?></h3>
              <span class="cupos"><b><?= $v['postulados'] ?></b> / <?= $v['cupos'] ?> lugares</span>
            </div>
            <p class="curso-datos"><?= e($v['ubicacion']) ?> · <?= e($v['modalidad']) ?><?= $v['activa'] ? '' : ' · Cerrada' ?></p>
            <details>
              <summary>Postulaciones (<?= $v['postulados'] ?>)</summary>
              <?php foreach ($postulaciones[$v['id']] ?? [] as $p): ?>
                <div class="postulacion">
                  <div class="fila-entre">
                    <span><b><?= e($p['nombre']) ?></b> · <?= e($p['contacto']) ?> · <span class="chico"><?= hace($p['creado']) ?></span></span>
                    <form method="post">
                      <?= campoToken() ?>
                      <input type="hidden" name="accion" value="estado">
                      <input type="hidden" name="vacante" value="<?= $v['id'] ?>">
                      <input type="hidden" name="persona" value="<?= $p['usuario_id'] ?>">
                      <select name="estado" onchange="this.form.submit()" aria-label="Estado de la postulación">
                        <?php foreach ($estadosPostulacion as $estado): ?>
                          <option <?= $p['estado'] === $estado ? 'selected' : '' ?>><?= $estado ?></option>
                        <?php endforeach; ?>
                      </select>
                    </form>
                  </div>
                  <?php if ($p['mensaje']): ?><p><?= e($p['mensaje']) ?></p><?php endif; ?>
                </div>
              <?php endforeach; ?>
              <?php if (empty($postulaciones[$v['id']])): ?><p class="chico">Aún no hay postulaciones.</p><?php endif; ?>
            </details>
            <?php if ($v['activa']): ?>
              <form method="post">
                <?= campoToken() ?>
                <input type="hidden" name="accion" value="cerrar">
                <input type="hidden" name="vacante" value="<?= $v['id'] ?>">
                <button class="enlace" type="submit">Cerrar vacante</button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
        <?php if (!$vacantes): ?><p class="suave">Aún no publicas vacantes.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
