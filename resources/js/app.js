import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
// Los diálogos usan los mismos botones que el resto de la aplicación: sin esto SweetAlert2 pinta
// los suyos, y aquí había un azul y un rojo sueltos que no eran de la paleta.
const Dialogo = Swal.mixin({
    buttonsStyling: false,
    customClass: { actions: 'grupo-acciones', cancelButton: 'boton tono-neutro' },
    reverseButtons: true,
});
window.Swal = Dialogo;

import { vincularFechas, initFlatpickr } from './utils';
import { initScrollTop } from './scroll-top';
import './components/historial';
import './components/incidencias';
import { initIncidenciasForm } from './components/incidencias';
import './components/familiares';
import './components/visitas-habitacion';
import './imprimir';

// Nombre del archivo elegido en los campos de archivo propios (x-campo-archivo): el botón
// nativo del navegador no se puede traducir, así que se oculta y se escribe aquí el nombre.
document.addEventListener('change', function (e) {
    const input = e.target.closest('[data-campo-archivo] input[type="file"]');
    if (!input) return;
    const salida = input.closest('[data-campo-archivo]').querySelector('[data-campo-archivo-nombre]');
    if (!salida) return;
    salida.textContent = input.files && input.files[0] ? input.files[0].name : salida.dataset.vacio;
});

// Marcar o desmarcar de golpe las casillas de un grupo, dentro del formulario del botón.
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-marcar],[data-desmarcar]');
    if (!btn) return;
    const marcar = btn.hasAttribute('data-marcar');
    const nombre = btn.dataset.marcar || btn.dataset.desmarcar;
    const ambito = btn.closest('form') || document;
    ambito.querySelectorAll(`[name="${nombre}"]`).forEach((casilla) => {
        casilla.checked = marcar;
    });
});

// Acciones que exigen motivo (anular, retirar...); se pide en el diálogo y se envía en el campo oculto "motivo"
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-swal-motivo]');
    if (!btn) return;
    e.preventDefault();

    Dialogo.fire({
        title: btn.dataset.title,
        text: btn.dataset.text,
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Motivo',
        inputValidator: (valor) => (valor || '').trim().length >= 10 ? null : 'Indica el motivo (mínimo 10 caracteres).',
        showCancelButton: true,
        customClass: { actions: 'grupo-acciones', confirmButton: 'boton tono-peligro', cancelButton: 'boton tono-neutro' },
        confirmButtonText: btn.dataset.confirmar || 'Confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        const form = btn.closest('form');
        form.querySelector('input[name="motivo"]').value = result.value.trim();
        form.submit();
    });
});

document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-swal-submit],[data-swal-delete],[data-swal-baja],[data-swal-editar]');
    if (!btn) return;
    e.preventDefault();

    const action =
        btn.hasAttribute('data-swal-submit') ? 'submit' :
        btn.hasAttribute('data-swal-delete') ? 'delete' :
        btn.hasAttribute('data-swal-baja') ? 'baja' :
        btn.hasAttribute('data-swal-editar') ? 'editar' : null;

    const configs = {
        submit:  { icon: 'question', boton: 'boton boton-primario', confirmButtonText: 'Sí' },
        delete:  { icon: 'warning',  boton: 'boton tono-peligro', confirmButtonText: 'Sí, eliminar' },
        baja:    { icon: 'warning',  boton: 'boton tono-peligro', confirmButtonText: 'Sí, dar de baja' },
        editar:  { icon: 'question', boton: 'boton boton-primario', confirmButtonText: 'Sí, guardar' },
    };

    const cfg = configs[action];
    if (!cfg) return;

    Dialogo.fire({
        title: btn.dataset.title,
        text: btn.dataset.text,
        icon: cfg.icon,
        showCancelButton: true,
        customClass: { actions: 'grupo-acciones', confirmButton: cfg.boton, cancelButton: 'boton tono-neutro' },
        confirmButtonText: cfg.confirmButtonText,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        if (action === 'submit') {
            btn.closest('form')?.submit();
        } else {
            const form = document.getElementById(btn.dataset.formId);
            if (form) form.submit();
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    initScrollTop();
    initFlatpickr();
    initIncidenciasForm();
    vincularFechas('filter_desde', 'filter_hasta');
    vincularFechas('ingreso_desde', 'ingreso_hasta');
    vincularFechas('nac_desde', 'nac_hasta');
});

Alpine.start();
