import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';

export function initFlatpickr() {
    document.querySelectorAll('input[type="date"]:not([readonly]):not([disabled])').forEach(el => {
        if (el._flatpickr) return;

        flatpickr(el, {
            locale: Spanish,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j/m/Y',
            allowInput: true,
            placeholder: 'dd/mm/aaaa',
            onReady: function(_, __, fp) {
                fp.altInput && fp.altInput.setAttribute('placeholder', 'dd/mm/aaaa');
            },
            onChange: function(selectedDates, dateStr) {
                this._input.dispatchEvent(new Event('input', { bubbles: true }));
            },
        });
    });

    document.querySelectorAll('input[type="time"]:not([readonly]):not([disabled])').forEach(el => {
        if (el._flatpickr) return;

        flatpickr(el, {
            locale: Spanish,
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            allowInput: true,
            placeholder: 'hh:mm',
            onReady: function(_, __, fp) {
                fp.altInput && fp.altInput.setAttribute('placeholder', 'hh:mm');
            },
            onChange: function(selectedDates, dateStr) {
                this._input.dispatchEvent(new Event('input', { bubbles: true }));
            },
        });
    });
}

export function vincularFechas(desdeId, hastaId) {
    const d = document.getElementById(desdeId);
    const h = document.getElementById(hastaId);
    if (!d || !h) return;

    const flatpickrD = d._flatpickr;
    const flatpickrH = h._flatpickr;

    const actualizar = () => {
        if (flatpickrD) flatpickrD.set('maxDate', h.value || null);
        else d.max = h.value || '';
        if (flatpickrH) flatpickrH.set('minDate', d.value || null);
        else h.min = d.value || '';
    };

    d.addEventListener('change', actualizar);
    h.addEventListener('change', actualizar);
    actualizar();
}
