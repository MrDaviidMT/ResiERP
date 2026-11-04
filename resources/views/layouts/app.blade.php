@use('App\Enums\Permission', 'P')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ResiERP') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">

            <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/50 lg:hidden" x-cloak></div>

            <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-marca-900 text-marca-100 flex flex-col justify-between border-r border-marca-800 transform transition-transform duration-200 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:z-auto"
                   :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
                <div class="overflow-y-auto">
                    <div class="p-6 border-b border-marca-800 flex items-center justify-between">
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 hover:opacity-80 transition">
                            <x-lucide-building-2 class="w-7 h-7 text-white shrink-0" />
                            <div>
                                <h2 class="text-white font-bold text-lg tracking-wide">ResiERP</h2>
                                <span class="text-xs text-marca-200 font-medium">Panel Clínico</span>
                            </div>
                        </a>
                        <button @click="sidebarOpen = false" title="Cerrar menú" class="lg:hidden accion tono-neutro" aria-label="Cerrar menú">
                            <x-lucide-x class="w-5 h-5" />
                        </button>
                    </div>

                    <nav class="mt-6 px-4 space-y-1">
                        <a href="{{ route('dashboard') }}" 
                           class="nav-item" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                            <x-lucide-bar-chart-4 class="w-5 h-5 shrink-0" />
                            <span>Inicio</span>
                        </a>

                        @can(P::ResidentesVer)
                        <a href="{{ route('residentes.index') }}" 
                           class="nav-item" @if(request()->routeIs('residentes.*')) aria-current="page" @endif>
                            <x-lucide-users class="w-5 h-5 shrink-0" />
                            <span>Residentes</span>
                        </a>
                        @endcan

                        @can(P::FamiliaresVer)
                        <a href="{{ route('familiares.lista') }}" 
                           class="nav-item" @if(request()->routeIs('familiares.*')) aria-current="page" @endif>
                            <x-lucide-contact class="w-5 h-5 shrink-0" />
                            <span>Familiares</span>
                        </a>
                        @endcan

                        @can('viewAny', \App\Models\Informe::class)
                        <a href="{{ route('informes.index') }}" 
                           class="nav-item" @if(request()->routeIs('informes.*')) aria-current="page" @endif>
                            <x-lucide-file-text class="w-5 h-5 shrink-0" />
                            <span>Informes</span>
                        </a>
                        @endcan

                        @can(P::ActividadesVer)
                        <a href="{{ route('actividades.index') }}" 
                           class="nav-item" @if(request()->routeIs('actividades.*')) aria-current="page" @endif>
                            <x-lucide-calendar class="w-5 h-5 shrink-0" />
                            <span>Actividades</span>
                        </a>
                        @endcan

                        @can(P::HabitacionesVer)
                        <a href="{{ route('habitaciones.index') }}" 
                           class="nav-item" @if(request()->routeIs('habitaciones.*')) aria-current="page" @endif>
                            <x-lucide-home class="w-5 h-5 shrink-0" />
                            <span>Habitaciones</span>
                        </a>
                        @endcan

                        @can(P::IncidenciasVer)
                        <a href="{{ route('incidencias.index') }}" 
                           class="nav-item" @if(request()->routeIs('incidencias.*')) aria-current="page" @endif>
                            <x-lucide-triangle-alert class="w-5 h-5 shrink-0" />
                            <span>Incidencias</span>
                        </a>
                        @endcan

                        @can(P::MenusGestionar)
                        <a href="{{ route('menus.index') }}" 
                           class="nav-item" @if(request()->routeIs('menus.*')) aria-current="page" @endif>
                            <x-lucide-utensils-crossed class="w-5 h-5 shrink-0" />
                            <span>Menús</span>
                        </a>
                        @endcan

                        <a href="{{ route('fichajes.index') }}" 
                           class="nav-item" @if(request()->routeIs('fichajes.*')) aria-current="page" @endif>
                            <x-lucide-clock class="w-5 h-5 shrink-0" />
                            <span>Fichajes</span>
                        </a>

                        @can(P::EmpleadosVer)
                        <a href="{{ route('users.index') }}" 
                           class="nav-item" @if(request()->routeIs('users.*')) aria-current="page" @endif>
                            <x-lucide-shield class="w-5 h-5 shrink-0" />
                            <span>Usuarios</span>
                        </a>
                        @endcan

                        @can(P::AuditoriaVer)
                        <a href="{{ route('logs.index') }}" 
                           class="nav-item" @if(request()->routeIs('logs.*')) aria-current="page" @endif>
                            <x-lucide-history class="w-5 h-5 shrink-0" />
                            <span>Logs</span>
                        </a>
                        @endcan
                    </nav>
                </div>

                <div class="p-4 border-t border-marca-800/50 text-center">
                    <span class="text-xs text-tinta-suave font-medium">v1.0.0</span>
                </div>
            </aside>

            <div class="flex-1 flex flex-col min-w-0">

                <nav class="barra-superior">
                    <div class="flex items-center gap-3 min-w-0">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden accion tono-neutro" title="Menú" aria-label="Menú">
                            <x-lucide-menu />
                        </button>
                        @if($volver)
                            <a href="{{ $volver }}" class="enlace-volver">
                                <x-lucide-arrow-left />{{ $volverTexto }}
                            </a>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @php $fichajeActivo = auth()->user()->fichajes()->whereNull('salida')->first(); @endphp
                        <span @class(['etiqueta', 'tono-exito' => $fichajeActivo, 'tono-aviso' => ! $fichajeActivo])>
                            {{ $fichajeActivo ? 'Fichado' : 'Sin fichar' }}
                        </span>
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button title="Menú de usuario" class="boton tono-neutro">
                                    <x-lucide-user class="w-4 h-4" /><span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                                    <x-lucide-chevron-down />
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')" title="Ajustes de perfil" class="grupo-acciones">
                                    <x-lucide-user class="w-4 h-4" /> <span>{{ __('Mi Perfil') }}</span>
                                </x-dropdown-link>

                                <hr class="border-linea my-1">

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    {{-- Sin JavaScript: la CSP bloquearía un onclick --}}
                                    <button type="submit" title="Cerrar sesión"
                                            class="w-full px-4 py-2 text-start text-sm leading-5 hover:bg-fondo focus:outline-none focus:bg-fondo transition duration-150 ease-in-out cursor-pointer flex items-center space-x-2 text-peligro hover:text-peligro font-medium">
                                        <x-lucide-log-out class="w-4 h-4" /> <span>{{ __('Cerrar Sesión') }}</span>
                                    </button>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </nav>

                <main class="flex-1">
                    <div @class(['contenedor', 'contenedor--estrecho' => $estrecho])>
                        <x-avisos />
                        {{ $slot }}
                    </div>
                </main>
            </div>

        </div>

        <button id="btn-scroll-top" type="button" title="Volver arriba" aria-label="Volver arriba"
                class="boton boton-primario boton-subir">
            <x-lucide-arrow-up />
        </button>

        @stack('scripts')
    </body>
</html>
