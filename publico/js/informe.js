/* Vida Salud — CU19 · Descarga el informe de resultado como PDF (html2pdf.js convierte la hoja en un
   PDF A4). Si la librería no cargó, se usa la impresión del navegador («Guardar como PDF»). */
(function () {
  document.addEventListener('click', function (e) {
    var boton = e.target.closest('[data-descargar-pdf]');
    if (!boton) return;
    e.preventDefault();
    var hoja = document.querySelector(boton.getAttribute('data-descargar-pdf'));
    if (!hoja || !window.html2pdf) { window.print(); return; }

    boton.disabled = true;
    window.html2pdf().set({
      margin: [8, 8, 8, 8],
      filename: boton.getAttribute('data-archivo') || 'resultado.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(hoja).save().then(function () { boton.disabled = false; }, function () {
      boton.disabled = false;
      window.print();
    });
  });
})();
