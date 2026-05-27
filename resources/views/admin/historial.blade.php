@extends('layouts.admin')

@section('title', 'Historial de Movimientos')

@section('content')
    <div class="header">
        <div>
            <h1>Historial de Movimientos</h1>
            <p>Registro completo de todas las operaciones del sistema</p>
        </div>
    </div>

    <div class="filters">
        <div class="search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar en el historial...">
        </div>

        <select>
            <option>Todos los tipos</option>
            <option>Asignaci&oacute;n</option>
            <option>Reasignaci&oacute;n</option>
            <option>Alta</option>
            <option>Baja</option>
            <option>Modificaci&oacute;n</option>
        </select>

        <button type="button" class="btn">
            <i class="fa-solid fa-filter"></i>
            Filtrar
        </button>

        <button type="button" class="btn">
            <i class="fa-solid fa-file-export"></i>
            Exportar
        </button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>Bien</th>
                    <th>Responsable anterior</th>
                    <th>Responsable nuevo</th>
                    <th>Detalles</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td><span class="tag asignacion">Asignaci&oacute;n</span></td>
                    <td>2024-05-20<br>10:30</td>
                    <td>Admin Usuario</td>
                    <td>Laptop Dell Latitude 5420</td>
                    <td>-</td>
                    <td>Juan P&eacute;rez</td>
                    <td>Asignaci&oacute;n inicial del bien</td>
                </tr>

                <tr>
                    <td><span class="tag reasignacion">Reasignaci&oacute;n</span></td>
                    <td>2024-05-19<br>14:15</td>
                    <td>Admin Usuario</td>
                    <td>Monitor LG 27 pulgadas</td>
                    <td>Carlos L&oacute;pez</td>
                    <td>Mar&iacute;a Garc&iacute;a</td>
                    <td>Cambio de &aacute;rea - Administraci&oacute;n</td>
                </tr>

                <tr>
                    <td><span class="tag alta">Alta</span></td>
                    <td>2024-05-18<br>09:00</td>
                    <td>Admin Usuario</td>
                    <td>Escritorio ejecutivo</td>
                    <td>-</td>
                    <td>-</td>
                    <td>Registro de nuevo bien en almac&eacute;n</td>
                </tr>

                <tr>
                    <td><span class="tag baja">Baja</span></td>
                    <td>2024-05-17<br>16:45</td>
                    <td>Admin Usuario</td>
                    <td>Impresora HP LaserJet 1020</td>
                    <td>Ana Mart&iacute;nez</td>
                    <td>-</td>
                    <td>Equipo obsoleto - fin de vida &uacute;til</td>
                </tr>

                <tr>
                    <td><span class="tag modificacion">Modificaci&oacute;n</span></td>
                    <td>2024-05-16<br>11:20</td>
                    <td>Admin Usuario</td>
                    <td>Silla ergon&oacute;mica</td>
                    <td>Juan P&eacute;rez</td>
                    <td>Juan P&eacute;rez</td>
                    <td>Actualizaci&oacute;n de datos - cambio de modelo</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
