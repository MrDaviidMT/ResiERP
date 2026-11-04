<x-guest-layout>
    <div class="mb-4 text-sm text-tinta-suave">
        Gracias por registrarte. Antes de empezar, necesitas verificar tu dirección de correo electrónico haciendo clic en el enlace que te hemos enviado. Si no recibiste el correo, te enviaremos otro.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-exito">
            Se ha enviado un nuevo enlace de verificación a la dirección de correo que registraste.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button title="Reenviar correo de verificación">
                    Reenviar correo de verificación
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" title="Cerrar sesión" class="underline text-sm text-tinta-suave hover:text-tinta rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
