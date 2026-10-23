/* Vida Salud — Modo claro / oscuro
   El script inline del <head> aplica el tema antes de pintar para evitar parpadeo.
   Este archivo solo gestiona el botón sol/luna. */
(function () {
  var CLAVE_TEMA = 'vs-tema';

  function temaActual() {
    return document.documentElement.classList.contains('dark') ? 'oscuro' : 'claro';
  }

  function aplicarTema(tema) {
    document.documentElement.classList.toggle('dark', tema === 'oscuro');
    try { localStorage.setItem(CLAVE_TEMA, tema); } catch (e) {}
    document.querySelectorAll('[data-accion="alternar-tema"]').forEach(function (boton) {
      boton.setAttribute('aria-pressed', tema === 'oscuro' ? 'true' : 'false');
      boton.setAttribute('aria-label', tema === 'oscuro' ? 'Activar modo claro' : 'Activar modo oscuro');
    });
  }

  function alternarTema() {
    aplicarTema(temaActual() === 'oscuro' ? 'claro' : 'oscuro');
  }

  window.alternarTema = alternarTema;
  window.aplicarTema = aplicarTema;

  document.addEventListener('DOMContentLoaded', function () {
    aplicarTema(temaActual());
    document.querySelectorAll('[data-accion="alternar-tema"]').forEach(function (boton) {
      boton.addEventListener('click', alternarTema);
    });
  });
})();
