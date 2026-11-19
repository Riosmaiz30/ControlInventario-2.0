@extends('layouts.admin')

@section('title', 'Despachos a Sedes')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">Despachos a Sedes</h1>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Despachos</li>
            </ol>
        </nav>
    </div>
@stop

@section('content')
    <livewire:gestion-despachos />
@stop
