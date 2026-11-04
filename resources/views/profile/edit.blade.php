<x-app-layout estrecho>
    <h1 class="titulo-pagina mb-6">Mi perfil</h1>

    <div>
        <div class="space-y-6">
            <div class="tarjeta p-6">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="tarjeta p-6">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
