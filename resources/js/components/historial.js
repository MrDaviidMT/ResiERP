import Alpine from 'alpinejs';

Alpine.data('crudForm', (config) => ({
    editId: null,
    detalle: config.hasDetalle !== false ? null : undefined,
    form: { ...config.defaultForm },
    editar(item) {
        this.editId = item.id;
        this.form = { ...item };
        this.$refs.form.action = config.basePath + '/' + item.id;
        this.$refs.form.querySelector('[name=_method]').value = 'PUT';
        this.$refs.form.parentElement.classList.remove('hidden');
    },
    iniciarNuevo() {
        this.editId = null;
        this.form = { ...config.defaultForm };
        this.$refs.form.action = config.storeUrl;
        this.$refs.form.querySelector('[name=_method]').value = 'POST';
        this.$refs.form.parentElement.classList.remove('hidden');
    },
    cancelar() {
        this.editId = null;
        this.form = { ...config.defaultForm };
        this.$refs.form.parentElement.classList.add('hidden');
    }
}));
