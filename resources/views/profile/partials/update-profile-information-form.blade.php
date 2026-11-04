<section>
    <header>
        <h2 class="text-lg font-medium text-tinta">
            {{ __('Información del Perfil') }}
        </h2>

        <p class="mt-1 text-sm text-tinta-suave">
            {{ __("Actualiza la información de tu perfil y dirección de correo electrónico.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nombre')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

        <div>
            <x-input-label for="role" :value="__('Rol')" />
            <div class="mt-1 p-2 bg-fondo rounded-md border border-linea text-sm text-tinta">
                {{ $user->rol()?->label() }}
            </div>
        </div>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-tinta">
                        {{ __('Tu dirección de correo electrónico no está verificada.') }}

                        <button form="send-verification" title="Reenviar verificación de correo" class="underline text-sm text-tinta-suave hover:text-tinta rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2">
                            {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-exito">
                            {{ __('Se ha enviado un nuevo enlace de verificación a tu correo electrónico.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button title="Guardar cambios"><x-lucide-user-check class="w-4 h-4" /><span class="hidden sm:inline ml-1"> {{ __('Guardar') }}</span></x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-tinta-suave"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</section>
