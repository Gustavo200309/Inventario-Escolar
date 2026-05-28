@extends('layouts.admin')

@section('title', 'Gestion de Asignaciones')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Asignaciones</h1>
            <p>Administra las asignaciones de bienes al personal</p>
        </div>

        <button type="button" class="btn-agregar">
            <i class="fa-solid fa-plus"></i>
            Nueva asignaci&oacute;n
        </button>
    </div>

    <div class="buscador">
        <div class="input-buscar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar por bien, responsable o &aacute;rea...">
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Bien</th>
                    <th>Responsable</th>
                    <th>&Aacute;rea</th>
                    <th>Fecha de asignaci&oacute;n</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Laptop Dell Latitude 5420</td>
                    <td>Juan P&eacute;rez</td>
                    <td>Sistemas</td>
                    <td>14/1/2024</td>
                    <td><span class="status">Activo</span></td>
                    <td class="acciones">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <i class="fa-solid fa-right-left"></i>
                    </td>
                </tr>

                <tr>
                    <td>Monitor LG 27 pulgadas</td>
                    <td>Mar&iacute;a Garc&iacute;a</td>
                    <td>Administraci&oacute;n</td>
                    <td>19/2/2024</td>
                    <td><span class="status">Activo</span></td>
                    <td class="acciones">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <i class="fa-solid fa-right-left"></i>
                    </td>
                </tr>

                <tr>
                    <td>Silla ergon&oacute;mica</td>
                    <td>Carlos L&oacute;pez</td>
                    <td>Recursos Humanos</td>
                    <td>9/3/2024</td>
                    <td><span class="status">Activo</span></td>
                    <td class="acciones">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <i class="fa-solid fa-right-left"></i>
                    </td>
                </tr>

                <tr>
                    <td>Impresora HP LaserJet</td>
                    <td>Ana Mart&iacute;nez</td>
                    <td>Sistemas</td>
                    <td>24/1/2024</td>
                    <td><span class="status">Activo</span></td>
                    <td class="acciones">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <i class="fa-solid fa-right-left"></i>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="tabla-footer">Mostrando 4 de 4 asignaciones</div>
    </div>
@endsection
