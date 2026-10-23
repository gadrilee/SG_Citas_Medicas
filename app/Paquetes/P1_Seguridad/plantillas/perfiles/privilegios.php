<?php
/**
 * CU02 · Perfiles y privilegios.
 * @var array $perfiles  con conteo de usuarios activos
 * @var array $perfil    perfil seleccionado
 * @var array $grupos    [paquete => privilegios]
 * @var array $fijos     privilegios que no se pueden quitar a este perfil
 * @var array $iconos
 */
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU02 · Control de acceso</p>
    <h1 class="titulo-pagina">Perfiles y privilegios</h1>
    <p class="texto">Define qué puede hacer cada uno de los cuatro perfiles del sistema.</p>
  </div>
</div>

<div class="grid gap-6 lg:grid-cols-[280px_1fr]">
  <nav aria-label="Perfiles" class="glass p-3 h-fit">
    <ul class="flex lg:flex-col gap-1 overflow-x-auto">
      <?php foreach ($perfiles as $item): ?>
        <?php $actual = (int) $item['id'] === (int) $perfil['id']; ?>
        <li><a href="?perfil=<?= e($item['id']) ?>" class="item-menu whitespace-nowrap<?= $actual ? ' activo' : '' ?>"<?= $actual ? ' aria-current="page"' : '' ?>><i data-lucide="<?= e($iconos[$item['nombre']] ?? 'user') ?>"></i><span class="flex-1"><?= e(nombre_perfil($item['nombre'])) ?></span><span class="insignia insignia-neutra sin-punto numeros"><?= e($item['usuarios']) ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <form method="post" action="<?= e(url('/perfiles/' . $perfil['id'] . '/privilegios')) ?>" class="glass tabla-contenedor">
    <?= csrf_campo() ?>
    <?= metodo_campo('PUT') ?>
    <div class="flex flex-wrap items-center justify-between gap-3 p-6 pb-4">
      <div>
        <h2 class="font-semibold"><?= e(nombre_perfil($perfil['nombre'])) ?></h2>
        <p class="text-sm text-subtle"><?= e($perfil['descripcion']) ?></p>
      </div>
      <?php if ($fijos): ?>
        <div class="alerta alerta-aviso py-2"><i data-lucide="lock"></i><p class="text-sm">El administrador conserva siempre la gestión de usuarios y perfiles.</p></div>
      <?php endif; ?>
    </div>
    <div class="px-2 md:px-0">
      <table class="tabla matriz">
        <thead>
          <tr><th scope="col">Privilegio</th><th scope="col">Operación</th><th scope="col" class="centro">Permitido</th></tr>
        </thead>
        <tbody>
          <?php foreach ($grupos as $paquete => $privilegios): ?>
            <tr class="grupo-modulo"><td colspan="3" data-etiqueta=""><?= e($paquete) ?></td></tr>
            <?php foreach ($privilegios as $privilegio): ?>
              <?php $fijo = in_array($privilegio['nombre'], $fijos, true); ?>
              <tr>
                <th scope="row" class="font-mono text-sm font-medium"><?= e($privilegio['nombre']) ?></th>
                <td><?= e($privilegio['descripcion']) ?></td>
                <td class="centro">
                  <input class="casilla" type="checkbox" name="privilegios[]" value="<?= e($privilegio['id']) ?>"
                         <?= $privilegio['asignado'] || $fijo ? 'checked' : '' ?> <?= $fijo ? 'disabled' : '' ?>
                         aria-label="<?= e($privilegio['nombre']) ?>">
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 p-6">
      <button type="reset" class="btn-fantasma">Descartar cambios</button>
      <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar privilegios</button>
    </div>
  </form>
</div>
