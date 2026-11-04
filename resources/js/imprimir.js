// Botones de imprimir. Antes era onclick="window.print()" escrito en el HTML, que la CSP
// bloquea (script-src sin 'unsafe-inline', hallazgo S13): el botón no hacía nada.
//
// Se carga dos veces a propósito: desde app.js para la aplicación, y como entrada propia de
// Vite en la copia de la historia clínica, que es una página suelta y no trae el bundle
// completo (no necesita Alpine ni SweetAlert2 para imprimir).
document.addEventListener('click', function (e) {
    if (e.target.closest('[data-imprimir]')) {
        window.print();
    }
});
