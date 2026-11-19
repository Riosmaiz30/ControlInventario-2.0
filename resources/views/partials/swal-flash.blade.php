{{-- Mensajes flash que el layout global convierte en alertas SweetAlert2.
     Se renderizan ocultos y el script del layout los detecta, incluso
     después de una actualización de Livewire. --}}
@foreach (['success', 'error', 'warning', 'info'] as $tipo)
    @if (session()->has($tipo))
        <div
            data-swal-flash
            data-type="{{ $tipo }}"
            data-message="{{ session($tipo) }}"
            hidden
        ></div>
    @endif
@endforeach
