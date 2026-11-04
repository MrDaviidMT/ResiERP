import Alpine from 'alpinejs';

Alpine.data('visitasApp', (config) => ({
    editId: null,
    form: { ...config.defaultForm },
    editar(item) {
        this.editId = item.id;
        this.form = { ...item };
        this.$refs.form.action = config.basePath + '/' + item.id;
        this.$refs.form.querySelector('[name=_method]').value = 'PUT';
    },
    iniciarNuevo() {
        this.editId = null;
        this.form = { ...config.defaultForm };
        this.$refs.form.action = config.storeUrl;
        this.$refs.form.querySelector('[name=_method]').value = 'POST';
    },
    cancelarEdit() {
        this.editId = null;
        this.form = { ...config.defaultForm };
        this.$refs.form.action = config.storeUrl;
        this.$refs.form.querySelector('[name=_method]').value = 'POST';
    }
}));
