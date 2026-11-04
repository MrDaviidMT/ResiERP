@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-linea-fuerte rounded-md bg-superficie p-2 border']) }}>
