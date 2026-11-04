import Alpine from 'alpinejs';

Alpine.data('createFamiliar', (config) => ({
    vinculaciones: config.vinculaciones || [],
    residentesList: config.residentesList || [],
    searchQuery: '',
    selectedResidente: config.preseleccionado || null,
    selectedParentesco: 'Hijo/a',
    selectedEmergencia: false,
    selectedAutorizado: true,
    selectedObservaciones: '',
    get resultados() {
        if (!this.searchQuery) return [];
        const q = this.searchQuery.toLowerCase();
        return this.residentesList.filter(r => {
            if (this.vinculaciones.some(v => v.residente_id == r.id)) return false;
            const fullName = (r.nombre + ' ' + r.apellidos).toLowerCase();
            const dniMatch = r.dni && r.dni.toLowerCase().includes(q);
            return fullName.includes(q) || dniMatch;
        });
    },
    seleccionar(r) {
        this.selectedResidente = r;
        this.searchQuery = '';
    },
    cancelarSeleccion() {
        this.selectedResidente = null;
    },
    addVinculacion() {
        if (!this.selectedResidente) return;
        this.vinculaciones.push({
            residente_id: this.selectedResidente.id,
            nombre_completo: this.selectedResidente.nombre + ' ' + this.selectedResidente.apellidos,
            habitacion_numero: this.selectedResidente.habitacion_numero,
            parentesco: this.selectedParentesco,
            es_emergencia: this.selectedEmergencia,
            autorizado: this.selectedAutorizado,
            observaciones: this.selectedObservaciones,
        });
        this.selectedResidente = null;
        this.selectedEmergencia = false;
        this.selectedAutorizado = true;
        this.selectedObservaciones = '';
    },
    removeVinculacion(index) {
        this.vinculaciones.splice(index, 1);
    }
}));

Alpine.data('editFamiliar', (config) => ({
    vinculaciones: config.vinculaciones || [],
    selectedResidenteId: '',
    selectedParentesco: 'Hijo/a',
    selectedEmergencia: false,
    selectedAutorizado: true,
    selectedObservaciones: '',
    residentesList: config.residentesList || [],
    get disponibles() {
        return this.residentesList.filter(r => !this.vinculaciones.some(v => v.residente_id == r.id));
    },
    addVinculacion() {
        if (!this.selectedResidenteId) return;
        const r = this.residentesList.find(r => r.id == this.selectedResidenteId);
        if (!r) return;
        this.vinculaciones.push({
            residente_id: r.id,
            nombre_completo: r.nombre + ' ' + r.apellidos,
            habitacion_numero: r.habitacion_numero,
            parentesco: this.selectedParentesco,
            es_emergencia: this.selectedEmergencia,
            autorizado: this.selectedAutorizado,
            observaciones: this.selectedObservaciones,
        });
        this.selectedResidenteId = '';
        this.selectedEmergencia = false;
        this.selectedAutorizado = true;
        this.selectedObservaciones = '';
    },
    removeVinculacion(index) {
        this.vinculaciones.splice(index, 1);
    }
}));

Alpine.data('vincularFamiliar', (config) => ({
    mostrarForm: false,
    searchQuery: '',
    familiarSeleccionado: null,
    familiares: config.familiares || [],
    idsVinculados: config.idsVinculados || [],
    get resultados() {
        if (!this.searchQuery) return [];
        const q = this.searchQuery.toLowerCase();
        return this.familiares.filter(f => {
            if (this.idsVinculados.includes(f.id)) return false;
            const nombreMatch = f.nombre.toLowerCase().includes(q);
            const dniMatch = f.dni && f.dni.toLowerCase().includes(q);
            return nombreMatch || dniMatch;
        });
    },
    seleccionar(f) {
        this.familiarSeleccionado = f;
        this.searchQuery = '';
    },
    cancelarSeleccion() {
        this.familiarSeleccionado = null;
        this.searchQuery = '';
    },
    toggleForm() {
        this.mostrarForm = !this.mostrarForm;
    }
}));
