<button {{ $attributes->merge(['type' => 'submit', 'class' => 'boton boton-primario']) }}>
    {{ $slot }}
</button>
