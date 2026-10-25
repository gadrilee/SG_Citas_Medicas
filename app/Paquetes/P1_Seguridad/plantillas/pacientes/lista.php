<?php
/** CU04 · Lista de pacientes. @var array $resultado Epaciente::listar */
$filtro = static fn (string $clave): string => (string) ($_GET[$clave] ?? '');
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU04 · Gestión de pacientes</p>
    <h1 class="titulo-pagina">Pacientes</h1>
    <p class="texto"><span class="numeros"><?= number_format($resultado['total'], 0, ',', '.') ?></span> pacientes encontrados.</p>
  </div>
  <a href="<?= e(url('/pacientes/nuevo')) ?>" class="btn-primario"><?= icono('user-plus') ?>Registrar paciente</a>
</div>

<form method="get" action="<?= e(url('/pacientes')) ?>" class="glass p-3 mb-6 flex flex-col md:flex-row gap-3" role="search" aria-label="Buscar pacientes">
  <div class="campo-con-icono flex-1">
    <i data-lucide="search"></i>
    <label for="p-buscar" class="sr-only">Buscar por CI, nombre o código</label>
    <input id="p-buscar" name="buscar" type="search" class="campo" placeholder="Buscar por CI, nombre o código…" value="<?= e($filtro('buscar')) ?>">
  </div>
  <label for="p-estado" class="sr-only">Estado</label>
  <select id="p-estado" name="activo" class="campo md:w-44">
    <option value="">Todos</option>
    <option value="1"<?= $filtro('activo') === '1' ? ' selected' : '' ?>>Activos</option>
    <option value="0"<?= $filtro('activo') === '0' ? ' selected' : '' ?>>Inactivos</option>
  </select>
  <button type="submit" class="btn-secundario"><?= icono('search') ?>Buscar</button>
</form>

<section class="glass tabla-contenedor" aria-label="Lista de pacientes">
  <?php if (!$resultado['filas']): ?>
    <?= parcial('estado_vacio', ['icono' => 'user-search', 'titulo' => 'No encontramos pacientes', 'texto' => 'Verifica el CI o nombre, o registra un paciente nuevo.', 'boton' => 'Registrar paciente', 'ruta' => '/pacientes/nuevo']) ?>
  <?php else: ?>
  <div class="px-2 pt-2 md:px-0 md:pt-0">
    <table class="tabla">
      <thead><tr><th scope="col">Código</th><th scope="col">Paciente</th><th scope="col">CI</th><th scope="col">Teléfono</th><th scope="col">Última cita</th><th scope="col">Estado</th><th scope="col" data-etiqueta=""><span class="sr-only">Acciones</span></th></tr></thead>
      <tbody>
        <?php foreach ($resultado['filas'] as $fila): ?>
          <?php $nombre = $fila['nombres'] . ' ' . $fila['apellidos']; ?>
          <tr class="<?= $fila['activo'] ? '' : 'opacity-70' ?>">
            <td class="font-mono"><?= e($fila['codigo']) ?></td>
            <td><a href="<?= e(url('/pacientes/' . $fila['id'])) ?>" class="flex items-center gap-3 font-medium hover:underline"><span class="avatar avatar-sm" aria-hidden="true"><?= e(iniciales($nombre)) ?></span><span><?= e($nombre) ?></span></a></td>
            <td class="numeros"><?= e($fila['ci'] ?: '—') ?></td>
            <td class="numeros"><?= e($fila['telefono']) ?></td>
            <td class="numeros"><?= e($fila['ultima_cita'] ? fecha_corta($fila['ultima_cita']) : '—') ?></td>
            <td><?= parcial('badge_estado', ['tipo' => 'activo', 'estado' => (bool) $fila['activo']]) ?></td>
            <td class="text-right"><div class="flex justify-end gap-1">
              <a href="<?= e(url('/pacientes/' . $fila['id'])) ?>" class="btn-icono sm" aria-label="Ver ficha de <?= e($nombre) ?>"><i data-lucide="eye" class="w-4 h-4"></i></a>
              <a href="<?= e(url('/pacientes/' . $fila['id'] . '/editar')) ?>" class="btn-icono sm" aria-label="Editar a <?= e($nombre) ?>"><i data-lucide="pencil" class="w-4 h-4"></i></a>
              <?php if ($fila['activo']): ?>
                <a href="<?= e(url('/citas/nueva?paciente_id=' . $fila['id'])) ?>" class="btn-icono sm" aria-label="Nueva cita para <?= e($nombre) ?>"><i data-lucide="calendar-plus" class="w-4 h-4"></i></a>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?= parcial('paginacion', ['resultado' => $resultado, 'nombre' => 'pacientes']) ?>
</section>
