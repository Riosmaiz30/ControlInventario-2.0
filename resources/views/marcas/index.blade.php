@extends('layouts.admin')

@section('title', 'Marcas')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">Marcas</h1>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/home') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Marcas</li>
            </ol>
        </nav>
    </div>
@stop

@section('content')
    <livewire:gestion-marcas />
@stop
