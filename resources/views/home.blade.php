@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">Panel de Control</h1>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active" aria-current="page">Inicio</li>
            </ol>
        </nav>
    </div>
@stop

@section('content')
    {{-- Bienvenida --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h4 class="mb-1">
                            Bienvenido, {{ auth()->user()->name }}
                        </h4>
                        <p class="text-body-secondary mb-0">
                            Resumen general del sistema de control de inventario.
                        </p>
                    </div>

                    {{-- <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('productos.index') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Gestionar productos
                        </a>
                        <a href="{{ route('despachos.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-paper-plane me-1"></i> Despachos
                        </a>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
   
    

    {{-- Indicadores por módulo --}}
    <div class="row">

        <div class="col-lg-4 col-6">
            <x-adminlte-small-box
                title="{{ $totalCategorias }}"
                text="Categorías"
                icon="fas fa-tags"
                theme="warning"
                url="{{ route('categorias.index') }}"
                url-text="Ver categorías"
                footer-icon="fas fa-arrow-circle-right"
            />
        </div>

        <div class="col-lg-4 col-6">
            <x-adminlte-small-box
                title="{{ $totalProductos }}"
                text="Productos"
                icon="fas fa-boxes-stacked"
                theme="primary"
                url="{{ route('productos.index') }}"
                url-text="Ver productos"
                footer-icon="fas fa-arrow-circle-right"
            />
        </div>

        <div class="col-lg-4 col-6">
            <x-adminlte-small-box
                title="{{ $totalLotes }}"
                text="Lotes / Entradas"
                icon="fas fa-arrow-right-to-bracket"
                theme="success"
                url="{{ route('lotes.index') }}"
                url-text="Ver entradas"
                footer-icon="fas fa-arrow-circle-right"
            />
        </div>

        <div class="col-lg-4 col-6">
            <x-adminlte-small-box
                title="{{ $totalProveedores }}"
                text="Proveedores"
                icon="fas fa-truck"
                theme="info"
                url="{{ route('proveedores.index') }}"
                url-text="Ver proveedores"
                footer-icon="fas fa-arrow-circle-right"
            />
        </div>

        <div class="col-lg-4 col-6">
            <x-adminlte-small-box
                title="—"
                text="Despachos"
                icon="fas fa-paper-plane"
                theme="dark"
                url="{{ route('despachos.index') }}"
                url-text="Ver módulo"
                footer-icon="fas fa-arrow-circle-right"
            />
        </div>

        {{-- <div class="col-lg-3 col-6">
            <x-adminlte-small-box
                title="{{ $totalEmpleados }}"
                text="Empleados"
                icon="fas fa-users"
                theme="secondary"
                url="{{ route('empleados.index') }}"
                url-text="Ver empleados"
                footer-icon="fas fa-arrow-circle-right"
            />
        </div>

        <div class="col-lg-3 col-6">
            <x-adminlte-small-box
                title="{{ $totalSedes }}"
                text="Sedes"
                icon="fas fa-location-dot"
                theme="danger"
                url="{{ route('sedes.index') }}"
                url-text="Ver sedes"
                footer-icon="fas fa-arrow-circle-right"
            />
        </div> --}}

        

        {{-- <div class="col-lg-3 col-6">
            <x-adminlte-small-box
                title="{{ $totalPuntosConsumo }} / 13"
                text="Puntos de consumo"
                icon="fas fa-store"
                theme="primary"
                url="{{ route('sedes.index') }}"
                url-text="Administrar sedes"
                footer-icon="fas fa-arrow-circle-right"
            />
        </div> --}}
    </div>

    {{-- Accesos rápidos --}}
    {{-- <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt me-2"></i> Accesos rápidos
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('proveedores.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-truck me-1"></i> Proveedores
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('categorias.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-tags me-1"></i> Categorías
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('productos.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-boxes-stacked me-1"></i> Productos
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('lotes.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-arrow-right-to-bracket me-1"></i> Lotes / Entradas
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('empleados.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users me-1"></i> Empleados
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('sedes.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-location-dot me-1"></i> Sedes
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <a href="{{ route('despachos.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i> Despachos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-store me-2"></i> Puntos de consumo
                    </h3>
                </div>

                <div class="card-body text-center">
                    <div class="display-5 fw-bold">{{ $totalPuntosConsumo }} / 13</div>
                    <p class="text-body-secondary mb-3">
                        sedes de tipo consumidor registradas
                    </p>
                    <a href="{{ route('sedes.index') }}" class="btn btn-info btn-sm">
                        <i class="fas fa-location-dot me-1"></i> Administrar sedes
                    </a>
                </div>
            </div>
        </div>
    </div> --}}
@stop
