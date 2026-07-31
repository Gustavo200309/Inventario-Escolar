@extends('layouts.admin')

@section('title', 'Bienes Pendientes')

@section('content')
    <div class="header">
        <div>
            <h1>Bienes Pendientes</h1>
            <p>Gestiona los bienes que requieren atencion</p>
        </div>
    </div>

    @include('admin.partials.pendientes-list')
@endsection
