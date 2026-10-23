<?php /** @var string $titulo */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ? $titulo . ' · ' : '') ?>Vida Salud — Clínica Vida Salud</title>
<meta name="description" content="Sistema web de gestión de citas médicas de la Clínica Vida Salud, Santa Cruz de la Sierra.">
<meta name="theme-color" content="#CFEBF0" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#02060B" media="(prefers-color-scheme: dark)">
<link rel="icon" href="<?= e(recurso('img/logo-vida-salud.png')) ?>">
<script>
  (function () {
    try {
      var t = localStorage.getItem('vs-tema');
      if (t === 'oscuro' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
    } catch (e) {}
  })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(recurso('css/app.css')) ?>">
