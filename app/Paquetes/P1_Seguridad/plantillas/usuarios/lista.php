<?php
/**
 * CU02 · Usuarios.
 * @var array      $resultado          Eusuario::listar (paginado)
 * @var array      $personasSinCuenta
 * @var array|null $editar             cuenta abierta en el modal de edición (?editar=ID)
 * @var int        $usuarioActualId
 * @var array      $perfiles           [id => nombre]
 * @var array      $errores
 * @var array      $anterior
 */
$filtro = static fn (string $clave): string => (string) ($_GET[$clave] ?? '');
$origenNueva = ($anterior['origen'] ?? '') === 'nueva';
$abrirNueva = $errores && !$editar;
$v = static fn (string $campo): string => e($anterior[$campo] ?? '');
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU02 · Cuentas de usuario</p>
    <h1 class="titulo-pagina">Usuarios</h1>
    <p class="texto">Administra las cuentas de acceso del personal, los médicos y los pacientes.</p>
  </div>
  <button type="button" class="btn-primario" data-abrir-modal="modal-usuario"><?= icono('user-plus') ?>Nueva cuenta</button>
</div>

<form method="get" action="<?= e(url('/usuarios')) ?>" class="glass p-3 mb-6 flex flex-col md:flex-row gap-3" role="search" aria-label="Filtrar usuarios">
  <div class="campo-con-icono flex-1">
    <i data-lucide="search"></i>
    <label for="filtro-buscar" class="sr-only">Buscar por usuario o persona</label>
    <input id="filtro-buscar" name="buscar" type="search" class="campo" placeholder="Buscar por usuario o nombre…" value="<?= e($filtro('buscar')) ?>">
  </div>
  <label for="filtro-perfil" class="sr-only">Perfil</label>
  <select id="filtro-perfil" name="perfil_id" class="campo md:w-52">
    <option value="">Todos los perfiles</option>
    <?php foreach ($perfiles as $id => $nombre): ?>
      <option value="<?= $id ?>"<?= $filtro('perfil_id') === (string) $id ? ' selected' : '' ?>><?= e($nombre) ?></option>
    <?php endforeach; ?>
  </select>
  <label for="filtro-estado" class="sr-only">Estado</label>
  <select id="filtro-estado" name="activo" class="campo md:w-44">
    <option value="">Todos los estados</option>
    <option value="1"<?= $filtro('activo') === '1' ? ' selected' : '' ?>>Activos</option>
    <option value="0"<?= $filtro('activo') === '0' ? ' selected' : '' ?>>Inactivos</option>
  </select>
  <button type="submit" class="btn-secundario"><?= icono('filter') ?>Filtrar</button>
</form>

<section class="glass tabla-contenedor" aria-label="Lista de usuarios">
  <?php if (!$resultado['filas']): ?>
    <?= parcial('estado_vacio', ['icono' => 'users', 'titulo' => 'No se encontraron usuarios', 'texto' => 'Prueba con otros filtros o crea una nueva cuenta.']) ?>
  <?php else: ?>
  <div class="px-2 pt-2 md:px-0 md:pt-0">
    <table class="tabla">
      <thead>
        <tr><th scope="col">Usuario</th><th scope="col">Persona</th><th scope="col">Perfil</th><th scope="col">Estado</th><th scope="col">Registro</th><th scope="col" data-etiqueta=""><span class="sr-only">Acciones</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($resultado['filas'] as $fila): ?>
          <?php $propia = (int) $fila['id'] === $usuarioActualId; ?>
          <tr class="<?= $fila['activo'] ? '' : 'opacity-70' ?>">
            <td class="font-mono"><?= e($fila['nombre_usuario']) ?><?= $propia ? ' <span class="chip">tú</span>' : '' ?></td>
            <td><?= e($fila['persona']) ?></td>
            <td><span class="insignia <?= e(clase_perfil($fila['perfil'])) ?>"><?= e(nombre_perfil($fila['perfil'])) ?></span></td>
            <td><?= parcial('badge_estado', ['tipo' => 'activo', 'estado' => (bool) $fila['activo']]) ?></td>
            <td class="numeros"><?= e(fecha_corta($fila['fecha_registro'])) ?></td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <a href="?<?= e(http_build_query(array_merge($_GET, ['editar' => $fila['id']]))) ?>" class="btn-icono sm" aria-label="Editar <?= e($fila['nombre_usuario']) ?>"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                <button type="button" class="btn-icono sm" data-abrir-modal="modal-restablecer" data-accion="<?= e(url('/usuarios/' . $fila['id'] . '/restablecer')) ?>" data-nombre="<?= e($fila['nombre_usuario']) ?>" aria-label="Restablecer contraseña de <?= e($fila['nombre_usuario']) ?>"><i data-lucide="key-round" class="w-4 h-4"></i></button>
                <?php if (!$fila['activo']): ?>
                  <form method="post" action="<?= e(url('/usuarios/' . $fila['id'] . '/activar')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-icono sm" aria-label="Reactivar <?= e($fila['nombre_usuario']) ?>"><i data-lucide="user-check" class="w-4 h-4"></i></button></form>
                <?php elseif ($propia): ?>
                  <button type="button" class="btn-icono sm" disabled aria-label="No puedes desactivar tu propia cuenta" title="No puedes desactivar tu propia cuenta"><i data-lucide="user-x" class="w-4 h-4"></i></button>
                <?php else: ?>
                  <button type="button" class="btn-icono sm" data-abrir-modal="modal-desactivar-usuario" data-accion="<?= e(url('/usuarios/' . $fila['id'] . '/desactivar')) ?>" data-nombre="<?= e($fila['nombre_usuario']) ?>" aria-label="Desactivar <?= e($fila['nombre_usuario']) ?>"><i data-lucide="user-x" class="w-4 h-4"></i></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?= parcial('paginacion', ['resultado' => $resultado, 'nombre' => 'usuarios']) ?>
</section>

<!-- Modal: Nueva cuenta -->
<div id="modal-usuario" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-usuario-titulo" hidden<?= $abrirNueva ? ' data-abrir-al-cargar' : '' ?>>
  <div class="modal-panel ancho">
    <form method="post" action="<?= e(url('/usuarios')) ?>" data-validar novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="origen" value="<?= $origenNueva ? 'nueva' : 'existente' ?>" data-origen>
      <div class="flex items-start justify-between gap-4 mb-6">
        <div>
          <h2 id="modal-usuario-titulo" class="text-lg font-semibold">Nueva cuenta</h2>
          <p class="texto text-sm">Asocia la cuenta a una persona existente o registra personal nuevo.</p>
        </div>
        <button type="button" class="btn-icono sm" data-cerrar-modal aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>

      <div class="glass-pill pestanas mb-5" role="tablist" aria-label="Origen de la persona" data-pestanas="origen">
        <button type="button" class="pestana" role="tab" id="tab-existente" aria-controls="panel-existente" aria-selected="<?= $origenNueva ? 'false' : 'true' ?>" data-pestana="existente" onclick="this.form.origen.value='existente'">Persona existente</button>
        <button type="button" class="pestana" role="tab" id="tab-nueva" aria-controls="panel-nueva" aria-selected="<?= $origenNueva ? 'true' : 'false' ?>" data-pestana="nueva" onclick="this.form.origen.value='nueva'">Registrar personal nuevo</button>
      </div>

      <div id="panel-existente" role="tabpanel" aria-labelledby="tab-existente" data-panel-pestana="origen:existente" class="mb-5">
        <label for="persona_id" class="etiqueta">Persona</label>
        <select id="persona_id" name="persona_id" class="campo<?= marca_error($errores, 'persona_id') ?>" required>
          <option value="">Selecciona una persona sin cuenta…</option>
          <?php foreach ($personasSinCuenta as $persona): ?>
            <option value="<?= e($persona['id']) ?>"<?= ($anterior['persona_id'] ?? '') === (string) $persona['id'] ? ' selected' : '' ?>><?= e($persona['nombre']) ?> · <?= e($persona['tipo']) ?><?= $persona['ci'] ? ' · CI ' . e($persona['ci']) : '' ?></option>
          <?php endforeach; ?>
        </select>
        <p class="ayuda-campo">Médicos y pacientes se registran primero en sus módulos (CU04 y CU05).</p>
        <?= error_campo($errores, 'persona_id') ?>
      </div>

      <div id="panel-nueva" role="tabpanel" aria-labelledby="tab-nueva" data-panel-pestana="origen:nueva" hidden>
        <div class="grid gap-4 sm:grid-cols-2 mb-5">
          <div><label for="u-ci" class="etiqueta">CI</label><input id="u-ci" name="ci" class="campo<?= marca_error($errores, 'ci') ?>" required maxlength="30" value="<?= $v('ci') ?>"><?= error_campo($errores, 'ci') ?></div>
          <div><label for="u-fecha-nacimiento" class="etiqueta">Fecha de nacimiento</label><input id="u-fecha-nacimiento" name="fecha_nacimiento" type="date" class="campo<?= marca_error($errores, 'fecha_nacimiento') ?>" required value="<?= $v('fecha_nacimiento') ?>"><?= error_campo($errores, 'fecha_nacimiento') ?></div>
          <div><label for="u-nombres" class="etiqueta">Nombres</label><input id="u-nombres" name="nombres" class="campo<?= marca_error($errores, 'nombres') ?>" required maxlength="100" autocomplete="given-name" value="<?= $v('nombres') ?>"><?= error_campo($errores, 'nombres') ?></div>
          <div><label for="u-apellidos" class="etiqueta">Apellidos</label><input id="u-apellidos" name="apellidos" class="campo<?= marca_error($errores, 'apellidos') ?>" required maxlength="100" autocomplete="family-name" value="<?= $v('apellidos') ?>"><?= error_campo($errores, 'apellidos') ?></div>
          <div><label for="u-telefono" class="etiqueta">Teléfono</label><input id="u-telefono" name="telefono" type="tel" class="campo<?= marca_error($errores, 'telefono') ?>" required inputmode="tel" value="<?= $v('telefono') ?>"><?= error_campo($errores, 'telefono') ?></div>
          <div><label for="u-correo" class="etiqueta">Correo</label><input id="u-correo" name="correo" type="email" class="campo<?= marca_error($errores, 'correo') ?>" value="<?= $v('correo') ?>"><?= error_campo($errores, 'correo') ?></div>
          <div><label for="u-fecha-ingreso" class="etiqueta">Fecha de ingreso</label><input id="u-fecha-ingreso" name="fecha_ingreso" type="date" class="campo<?= marca_error($errores, 'fecha_ingreso') ?>" required value="<?= e($anterior['fecha_ingreso'] ?? date('Y-m-d')) ?>"><?= error_campo($errores, 'fecha_ingreso') ?></div>
        </div>
      </div>

      <hr class="separador mb-5">
      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label for="nombre_usuario" class="etiqueta">Nombre de usuario</label>
          <input id="nombre_usuario" name="nombre_usuario" class="campo font-mono<?= marca_error($errores, 'nombre_usuario') ?>" required pattern="[a-z0-9._]{3,50}" data-mensaje-patron="Usa minúsculas, números, punto o guion bajo (3 a 50)." autocomplete="off" value="<?= $editar ? '' : $v('nombre_usuario') ?>">
          <?= $editar ? '' : error_campo($errores, 'nombre_usuario') ?>
        </div>
        <div>
          <label for="contrasena" class="etiqueta">Contraseña temporal</label>
          <input id="contrasena" name="contrasena" type="password" class="campo<?= marca_error($errores, 'contrasena') ?>" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-mensaje-patron="Al menos 8 caracteres con letras y números." autocomplete="new-password">
          <?= error_campo($errores, 'contrasena') ?>
        </div>
        <div>
          <label for="perfil_id" class="etiqueta">Perfil</label>
          <select id="perfil_id" name="perfil_id" class="campo<?= marca_error($errores, 'perfil_id') ?>" required>
            <option value="">Selecciona…</option>
            <?php foreach ($perfiles as $id => $nombre): ?>
              <option value="<?= $id ?>"<?= !$editar && ($anterior['perfil_id'] ?? '') === (string) $id ? ' selected' : '' ?>><?= e($nombre) ?></option>
            <?php endforeach; ?>
          </select>
          <?= $editar ? '' : error_campo($errores, 'perfil_id') ?>
        </div>
      </div>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <button type="button" class="btn-fantasma" data-cerrar-modal>Cancelar</button>
        <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar cuenta</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editar): ?>
<!-- Modal: Editar cuenta (se abre con ?editar=ID) -->
<div id="modal-editar-usuario" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-editar-titulo" hidden data-abrir-al-cargar>
  <div class="modal-panel">
    <form method="post" action="<?= e(url('/usuarios/' . $editar['id'])) ?>" data-validar novalidate>
      <?= csrf_campo() ?>
      <?= metodo_campo('PUT') ?>
      <div class="flex items-start justify-between gap-4 mb-6">
        <div>
          <h2 id="modal-editar-titulo" class="text-lg font-semibold">Editar cuenta</h2>
          <p class="texto text-sm"><?= e($editar['nombres'] . ' ' . $editar['apellidos']) ?></p>
        </div>
        <a href="<?= e(url('/usuarios')) ?>" class="btn-icono sm" aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></a>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label for="editar-nombre-usuario" class="etiqueta">Nombre de usuario</label>
          <input id="editar-nombre-usuario" name="nombre_usuario" class="campo font-mono<?= marca_error($errores, 'nombre_usuario') ?>" required pattern="[a-z0-9._]{3,50}" data-mensaje-patron="Usa minúsculas, números, punto o guion bajo (3 a 50)." value="<?= e(valor($anterior, $editar, 'nombre_usuario')) ?>">
          <?= error_campo($errores, 'nombre_usuario') ?>
        </div>
        <div>
          <label for="editar-perfil" class="etiqueta">Perfil</label>
          <select id="editar-perfil" name="perfil_id" class="campo<?= marca_error($errores, 'perfil_id') ?>" required>
            <?php foreach ($perfiles as $id => $nombre): ?>
              <option value="<?= $id ?>"<?= valor($anterior, $editar, 'perfil_id') === (string) $id ? ' selected' : '' ?>><?= e($nombre) ?></option>
            <?php endforeach; ?>
          </select>
          <?= error_campo($errores, 'perfil_id') ?>
        </div>
      </div>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <a href="<?= e(url('/usuarios')) ?>" class="btn-fantasma">Cancelar</a>
        <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?= parcial('modal_confirmar', [
    'id' => 'modal-restablecer', 'titulo' => 'Restablecer contraseña de {nombre}', 'tipo' => 'primario', 'icono' => 'key-round',
    'texto' => 'Se generará una contraseña temporal que verás en pantalla para entregarla al usuario.', 'boton' => 'Restablecer',
]) ?>
<?= parcial('modal_confirmar', [
    'id' => 'modal-desactivar-usuario', 'titulo' => '¿Desactivar la cuenta {nombre}?', 'icono' => 'user-x',
    'texto' => 'El usuario no podrá iniciar sesión hasta que la reactives. Sus registros se conservan.', 'boton' => 'Desactivar',
]) ?>
