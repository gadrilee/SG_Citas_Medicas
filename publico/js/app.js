/* Vida Salud — Conexión de la interfaz de V0 con el backend PHP.
   - Un botón con data-abrir-modal + data-accion cambia el action del formulario del modal
     (un solo modal de confirmación sirve para todas las filas de una tabla).
   - data-nombre rellena los [data-campo-modal="nombre"] del modal.
   - Un modal con data-abrir-al-cargar se abre solo (formulario devuelto con errores o edición). */
(function () {
  document.addEventListener('click', function (e) {
    var boton = e.target.closest('[data-abrir-modal][data-accion]');
    if (!boton) return;
    var modal = document.getElementById(boton.getAttribute('data-abrir-modal'));
    if (!modal) return;
    var formulario = modal.querySelector('form');
    if (formulario) formulario.setAttribute('action', boton.getAttribute('data-accion'));
    modal.querySelectorAll('[data-campo-modal]').forEach(function (nodo) {
      var valor = boton.getAttribute('data-' + nodo.getAttribute('data-campo-modal'));
      if (valor !== null) nodo.textContent = valor;
    });
  }, true);

  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.querySelector('.modal[data-abrir-al-cargar]');
    if (modal && window.abrirModal) window.abrirModal(modal.id);
  });
})();
