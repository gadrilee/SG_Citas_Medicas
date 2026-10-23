<?php
/**
 * @var array  $resultado ['total', 'pagina', 'paginas', 'desde', 'hasta'] de Modelo::paginar
 * @var string $nombre    "pacientes", "registros"…
 */
$pagina = $resultado['pagina'];
$paginas = $resultado['paginas'];
$consulta = $_GET;
$enlace = static function (int $numero) use ($consulta): string {
    return '?' . http_build_query(array_merge($consulta, ['pagina' => $numero]));
};
?>
<nav class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-4" aria-label="Paginación">
  <p class="text-sm text-subtle">Mostrando <?= $resultado['desde'] ?>–<?= $resultado['hasta'] ?> de <?= number_format($resultado['total'], 0, ',', '.') ?> <?= e($nombre) ?></p>
  <?php if ($paginas > 1): ?>
    <div class="paginacion glass-pill flex items-center gap-1 p-1">
      <?php if ($pagina > 1): ?>
        <a href="<?= e($enlace($pagina - 1)) ?>" aria-label="Página anterior"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
      <?php endif; ?>
      <?php for ($i = max(1, $pagina - 2); $i <= min($paginas, $pagina + 2); $i++): ?>
        <?php if ($i === $pagina): ?>
          <span class="actual" aria-current="page"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= e($enlace($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($pagina < $paginas): ?>
        <a href="<?= e($enlace($pagina + 1)) ?>" aria-label="Página siguiente"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</nav>
