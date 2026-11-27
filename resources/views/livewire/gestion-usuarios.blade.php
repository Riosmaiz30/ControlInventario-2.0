<div>
    {{-- Alertas SweetAlert2 --}}
    @include('partials.swal-flash')

    {{-- Tarjeta principal --}}
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0">
                <i class="bi bi-person-gear me-1"></i> Gestión de Usuarios
            </h3>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 260px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Buscar usuario..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Buscar usuarios"
                    >
                </div>

                <button type="button" class="btn btn-primary btn-sm" wire:click="crear">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo Usuario
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Correo electrónico</th>
                            <th class="text-center">Rol</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @forelse ($usuarios as $usuario)
                            <tr wire:key="usuario-{{ $usuario->id }}">
                                <td class="fw-semibold">{{ $usuario->name }}</td>
                                <td>{{ $usuario->email }}</td>
                                <td class="text-center">
                                    @php
                                        $colorRol = match ($usuario->role) {
                                            App\Models\User::ROLE_ADMINISTRADOR => 'danger',
                                            App\Models\User::ROLE_JEFE_ALMACEN => 'primary',
                                            default => 'success',
                                        };
                                    @endphp
                                    <span class="badge text-bg-{{ $colorRol }}">
                                        {{ $roles[$usuario->role] ?? 'Sin rol' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        wire:click="editar({{ $usuario->id }})"
                                        title="Editar usuario y rol"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No se encontraron usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($usuarios->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="text-muted">
                        Mostrando {{ $usuarios->firstItem() ?? 0 }}–{{ $usuarios->lastItem() ?? 0 }}
                        de {{ $usuarios->total() }} usuarios
                    </small>
                    {{ $usuarios->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

    {{-- Formulario (modal) --}}
    @if ($mostrarModal)
        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            style="background-color: rgba(0, 0, 0, .5);"
            wire:keydown.escape.window="cerrarModal"
            wire:click.self="cerrarModal"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="{{ $isEditing ? 'actualizar' : 'guardar' }}">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-{{ $isEditing ? 'pencil-square' : 'plus-circle' }} me-1"></i>
                                {{ $isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input
                                    id="name"
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name"
                                    placeholder="Nombre del usuario"
                                >
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input
                                    id="email"
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    wire:model="email"
                                    placeholder="correo@ejemplo.com"
                                >
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select
                                    id="role"
                                    class="form-select @error('role') is-invalid @enderror"
                                    wire:model="role"
                                >
                                    @foreach ($roles as $valor => $etiqueta)
                                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Contraseña
                                    @if ($isEditing)
                                        <span class="text-muted">(dejar vacía para conservar la actual)</span>
                                    @else
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    wire:model="password"
                                    autocomplete="new-password"
                                >
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    class="form-control"
                                    wire:model="password_confirmation"
                                    autocomplete="new-password"
                                >
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModal">
                                <i class="bi bi-x-lg me-1"></i> Cancelar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="guardar,actualizar"
                            >
                                <span
                                    wire:loading
                                    wire:target="guardar,actualizar"
                                    class="spinner-border spinner-border-sm me-1"
                                    role="status"
                                    aria-hidden="true"
                                ></span>
                                <i
                                    class="bi bi-save me-1"
                                    wire:loading.remove
                                    wire:target="guardar,actualizar"
                                ></i>
                                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
