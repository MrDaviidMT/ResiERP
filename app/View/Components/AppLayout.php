<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Lo que se repetía en cada página lo decide aquí la plantilla: la vista declara a dónde
     * se vuelve y si es una ficha estrecha, y el layout lo coloca siempre en el mismo sitio.
     */
    public function __construct(
        public ?string $volver = null,
        public string $volverTexto = 'Volver',
        public bool $estrecho = false,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
