@extends('layouts.admin')

@section('title', 'Gestión de Usuarios')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">Gestión de Usuarios</h1>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/home') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
            </ol>
        </nav>
    </div>
@stop

@section('content')
    <livewire:gestion-usuarios />
@stop
