<?php
/** @var string $perfil  administrador | recepcionista | medico | paciente */
$menu = (require RAIZ . '/app/Compartido/menu.php')[$perfil] ?? [];
$visibles = [];
foreach ($menu as $grupo => $items) {
    $items = array_values(array_filter($items, static fn (array $i): bool => $i[3] === 'autenticado' || puede($i[3])));
    if ($items) {
        $visibles[$grupo] = $items;
    }
}
// Ítem activo: la ruta más larga que coincide con la actual (así /citas/nueva no marca también /citas).
$actual = ruta_actual();
$activo = '';
foreach ($visibles as $items) {
    foreach ($items as $item) {
        $ruta = $item[2];
        if (($actual === $ruta || str_starts_with($actual, $ruta . '/')) && strlen($ruta) > strlen($activo)) {
            $activo = $ruta;
        }
    }
}
?>
<aside id="sidebar" class="sidebar glass" aria-label="Menú del <?= e(nombre_perfil($perfil)) ?>">
  <div class="flex items-center justify-between px-2 pb-2">
    <a href="<?= e(url('/panel')) ?>" class="flex items-center gap-2.5"><?= parcial('logo', ['tamano' => 34]) ?></a>
    <button type="button" class="btn-icono sm lg:hidden" data-alternar-menu="sidebar" aria-label="Cerrar menú"><i data-lucide="x" class="w-5 h-5"></i></button>
  </div>
  <nav class="flex flex-col gap-0.5 flex-1" aria-label="Módulos">
    <?php foreach ($visibles as $grupo => $items): ?>
      <p class="grupo-menu"><?= e($grupo) ?></p>
      <?php foreach ($items as [$texto, $icono, $ruta]): ?>
        <a href="<?= e(url($ruta)) ?>" class="item-menu<?= $ruta === $activo ? ' activo' : '' ?>"<?= $ruta === $activo ? ' aria-current="page"' : '' ?>><i data-lucide="<?= e($icono) ?>"></i><?= e($texto) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="glass pequena mt-4 p-4" style="border-radius:18px">
    <p class="text-sm font-semibold">¿Necesitas ayuda?</p>
    <p class="text-xs text-muted mt-1 leading-relaxed">Recepción: (3) 345-6789<br>Lun a Vie · 08:00–18:00</p>
  </div>
  <form method="post" action="<?= e(url('/logout')) ?>" class="mt-2">
    <?= csrf_campo() ?>
    <button type="submit" class="item-menu w-full bg-transparent border-0 cursor-pointer font-[inherit]"><i data-lucide="log-out"></i>Cerrar sesión</button>
  </form>
</aside>
