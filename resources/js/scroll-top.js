export function initScrollTop() {
    const btn = document.getElementById('btn-scroll-top');
    if (!btn) return;
    // Aparece tras bajar media pantalla: es proporcional, porque un valor fijo en píxeles es
    // mucho en un móvil apaisado y poco en un monitor alto.
    function toggle() {
        btn.classList.toggle('show', window.scrollY > window.innerHeight / 2);
    }
    window.addEventListener('scroll', toggle, { passive: true });
    window.addEventListener('resize', toggle, { passive: true });
    // El clic se registra aquí y no con onclick en el HTML: la CSP bloquea los manejadores en línea
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    toggle();
}
