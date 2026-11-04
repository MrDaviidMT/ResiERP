<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::orderBy('fecha', 'desc')->paginate(30);

        return view('menus.index', compact('menus'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => 'required|date|unique:menus,fecha',
            'desayuno' => 'nullable|string|max:255',
            'comida' => 'nullable|string|max:255',
            'merienda' => 'nullable|string|max:255',
            'cena' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        Menu::create($data);

        return redirect()->route('menus.index')->with('success', 'Menú del día creado.');
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'fecha' => 'required|date|unique:menus,fecha,'.$menu->id,
            'desayuno' => 'nullable|string|max:255',
            'comida' => 'nullable|string|max:255',
            'merienda' => 'nullable|string|max:255',
            'cena' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $menu->update($data);

        return redirect()->route('menus.index')->with('success', 'Menú actualizado.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('menus.index')->with('success', 'Menú eliminado.');
    }
}
