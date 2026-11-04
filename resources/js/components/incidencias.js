import Alpine from 'alpinejs';
import { vincularFechas } from '../utils';

Alpine.data('incidenciasApp', () => ({
    modalCrear: false
}));

export function initIncidenciasForm() {
    const categoriaSel = document.getElementById('categoria_selector');
    const wrapResidente = document.getElementById('wrapper_residente_modal');
    const inputResidente = document.getElementById('residente_id_input');
    const habSelect = document.getElementById('habitacion_select');

    if (!categoriaSel) return;

    function ajustarFormulario() {
        // El selector de residente no existe para quien no puede ver residentes (D11)
        if (!wrapResidente) return;
        if (categoriaSel.value === 'sanitaria') {
            wrapResidente.classList.remove('hidden');
            inputResidente.setAttribute('required', 'required');
        } else {
            wrapResidente.classList.add('hidden');
            inputResidente.removeAttribute('required');
            inputResidente.value = '';
        }
    }

    function filtrarResidentes() {
        if (!inputResidente) return;
        const habId = habSelect.value;
        const options = inputResidente.querySelectorAll('option');
        options.forEach(opt => {
            if (opt.value === '') return;
            opt.style.display = (!habId || opt.dataset.hab === habId) ? '' : 'none';
        });
        const current = inputResidente.querySelector(`option[value="${inputResidente.value}"]`);
        if (inputResidente.value && current?.style.display === 'none') {
            inputResidente.value = '';
        }
    }

    categoriaSel.addEventListener('change', ajustarFormulario);
    habSelect.addEventListener('change', filtrarResidentes);
    ajustarFormulario();

    vincularFechas('filter_registrada_desde', 'filter_registrada_hasta');
    vincularFechas('filter_resuelta_desde', 'filter_resuelta_hasta');
}
