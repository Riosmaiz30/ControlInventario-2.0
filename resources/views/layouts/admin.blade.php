{{-- Layout maestro del panel. Extiende la plantilla de Laravel-AdminLTE y
     centraliza los assets y el pie de página para todos los módulos. --}}
@extends('adminlte::page')

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
@endpush

@push('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        (() => {
            const titulos = {
                success: '¡Operación exitosa!',
                error: 'No se pudo completar',
                warning: 'Atención',
                info: 'Información',
            };

            const mostrarAlerta = (elemento) => {
                if (!elemento || !elemento.isConnected) {
                    return;
                }

                const tipo = elemento.dataset.type || 'info';
                const mensaje = elemento.dataset.message || '';
                const esToast = tipo === 'success' || tipo === 'info';

                // Se retira del DOM para que una nueva acción vuelva a mostrar la alerta.
                elemento.remove();

                Swal.fire({
                    icon: tipo,
                    title: titulos[tipo] || 'Aviso',
                    text: mensaje,
                    toast: esToast,
                    position: esToast ? 'top-end' : 'center',
                    timer: esToast ? 2800 : undefined,
                    timerProgressBar: esToast,
                    showConfirmButton: !esToast,
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#0d6efd',
                });
            };

            const buscarAlertas = (raiz = document) => {
                if (raiz.matches?.('[data-swal-flash]')) {
                    mostrarAlerta(raiz);
                }

                raiz.querySelectorAll?.('[data-swal-flash]').forEach(mostrarAlerta);
            };

            // Alertas presentes al cargar la página.
            buscarAlertas();
            document.addEventListener('DOMContentLoaded', () => buscarAlertas());
            document.addEventListener('livewire:navigated', () => buscarAlertas());

            // Alertas que aparecen después de una acción de Livewire.
            new MutationObserver((mutaciones) => {
                mutaciones.forEach((mutacion) => {
                    mutacion.addedNodes.forEach((nodo) => {
                        if (nodo.nodeType === Node.ELEMENT_NODE) {
                            buscarAlertas(nodo);
                        }
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        })();

        window.addEventListener('sedes-actualizadas', (event) => {
            const badge = document.querySelector('#menu-sedes .nav-badge');

            if (badge && event.detail) {
                badge.textContent = event.detail.count;
            }
        });

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-confirm-delete]');

            if (!button) {
                return;
            }

            // El clic nunca debe llegar directo a Livewire: primero se confirma.
            event.preventDefault();
            event.stopImmediatePropagation();

            const popup = Swal.getPopup();
            const dialogoAbierto = popup && !popup.classList.contains('swal2-toast');

            // Si ya hay una confirmación abierta, se evita duplicarla.
            if (dialogoAbierto) {
                return;
            }

            const entity = button.dataset.confirmEntity || 'este registro';

            const result = await Swal.fire({
                title: `¿Eliminar ${entity}?`,
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true,
            });

            if (!result.isConfirmed) {
                return;
            }

            const componentEl = button.closest('[wire\\:id]');
            const componentId = componentEl ? componentEl.getAttribute('wire:id') : null;
            const component = componentId ? Livewire.find(componentId) : null;

            if (!component) {
                console.error('No se encontró el componente Livewire para eliminar.');

                return;
            }

            await component.call(button.dataset.confirmMethod || 'eliminar', button.dataset.confirmId);
        }, true);

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-confirm-edit]');

            if (!button) {
                return;
            }

            // El clic nunca debe llegar directo a Livewire: primero se pide la clave.
            event.preventDefault();
            event.stopImmediatePropagation();

            const popup = Swal.getPopup();
            const dialogoAbierto = popup && !popup.classList.contains('swal2-toast');

            if (dialogoAbierto) {
                return;
            }

            if (button.dataset.claveConfigurada === '0') {
                await Swal.fire({
                    icon: 'info',
                    title: 'Clave no configurada',
                    text: 'Configura la clave de administrador en el módulo Auditoría del Sistema para poder modificar lotes.',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#0d6efd',
                });

                return;
            }

            const result = await Swal.fire({
                title: 'Clave de administrador',
                input: 'password',
                inputLabel: 'Ingresa la clave para modificar este lote',
                inputPlaceholder: 'Clave de administrador',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocomplete: 'new-password',
                },
                showCancelButton: true,
                confirmButtonText: 'Continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusCancel: true,
                preConfirm: (clave) => {
                    if (!clave) {
                        Swal.showValidationMessage('Debes ingresar la clave.');

                        return false;
                    }

                    return clave;
                },
            });

            if (!result.isConfirmed) {
                return;
            }

            const componentEl = button.closest('[wire\\:id]');
            const componentId = componentEl ? componentEl.getAttribute('wire:id') : null;
            const component = componentId ? Livewire.find(componentId) : null;

            if (!component) {
                console.error('No se encontró el componente Livewire para editar.');

                return;
            }

            await component.call('solicitarEdicion', button.dataset.editId, result.value);
        }, true);
    </script>
@endpush

@section('footer')
    <div class="float-end d-none d-sm-inline">
        Sistema de Control de Inventario
    </div>
    <strong>ControlInventario</strong> &copy; {{ date('Y') }}
@stop
