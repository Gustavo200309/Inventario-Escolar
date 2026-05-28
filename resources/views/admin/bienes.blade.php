@extends('layouts.admin')

@section('title', 'Gestion de Bienes')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Bienes</h1>
            <p>Administra inventario de bienes institucionales</p>
        </div>

        <button type="button" class="btn-agregar">
            <i class="fa-solid fa-plus"></i>
            Agregar bien
        </button>
    </div>

    <div class="buscador">
        <div class="input-buscar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar por nombre, serie o n&uacute;mero de inventario">
        </div>

        <select>
            <option>Todos los estados</option>
            <option>Asignado</option>
            <option>Disponible</option>
            <option>Mantenimiento</option>
        </select>

        <button type="button" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtros</button>
        <button type="button" class="btn-secundario"><i class="fa-solid fa-file-export"></i> Exportar</button>
    </div>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th>No. Inventario</th>
                    <th>ID SEP</th>
                    <th>Nombre del bien</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>INV-2026-001</td>
                    <td>SEP-001-2026</td>
                    <td>Laptop Dell Latitude 5420</td>
                    <td>Dell</td>
                    <td>Latitude 5420</td>
                    <td>$18,500</td>
                    <td><span class="estado asignado">Asignado</span></td>
                    <td>Juan P&eacute;rez</td>
                    <td class="acciones">
                        <i class="fa-solid fa-eye"></i>
                        <i class="fa-solid fa-pen"></i>
                        <i class="fa-solid fa-print"></i>
                        <i class="fa-solid fa-trash action-danger"></i>
                    </td>
                </tr>

                <tr>
                    <td>INV-2026-002</td>
                    <td>SEP-002-2026</td>
                    <td>Monitor LG 27"</td>
                    <td>LG</td>
                    <td>27MP400</td>
                    <td>$3,900</td>
                    <td><span class="estado asignado">Asignado</span></td>
                    <td>Mar&iacute;a L&oacute;pez</td>
                    <td class="acciones">
                        <i class="fa-solid fa-eye"></i>
                        <i class="fa-solid fa-pen"></i>
                        <i class="fa-solid fa-print"></i>
                        <i class="fa-solid fa-trash action-danger"></i>
                    </td>
                </tr>

                <tr>
                    <td>INV-2026-003</td>
                    <td>SEP-003-2026</td>
                    <td>Proyector Epson PowerLite</td>
                    <td>Epson</td>
                    <td>X49</td>
                    <td>$9,600</td>
                    <td><span class="estado disponible">Disponible</span></td>
                    <td>Almac&eacute;n General</td>
                    <td class="acciones">
                        <i class="fa-solid fa-eye"></i>
                        <i class="fa-solid fa-pen"></i>
                        <i class="fa-solid fa-print"></i>
                        <i class="fa-solid fa-trash action-danger"></i>
                    </td>
                </tr>

                <tr>
                    <td>INV-2026-004</td>
                    <td>SEP-004-2026</td>
                    <td>Silla Ergon&oacute;mica Ejecutiva</td>
                    <td>Herman Miller</td>
                    <td>Aeron</td>
                    <td>$12,400</td>
                    <td><span class="estado asignado">Asignado</span></td>
                    <td>Carlos Ram&iacute;rez</td>
                    <td class="acciones">
                        <i class="fa-solid fa-eye"></i>
                        <i class="fa-solid fa-pen"></i>
                        <i class="fa-solid fa-print"></i>
                        <i class="fa-solid fa-trash action-danger"></i>
                    </td>
                </tr>

                <tr>
                    <td>INV-2026-005</td>
                    <td>SEP-005-2026</td>
                    <td>Impresora HP LaserJet</td>
                    <td>HP</td>
                    <td>LaserJet Pro M404n</td>
                    <td>$5,200</td>
                    <td><span class="estado mantenimiento">En mantenimiento</span></td>
                    <td>Ana Mart&iacute;nez</td>
                    <td class="acciones">
                        <i class="fa-solid fa-eye"></i>
                        <i class="fa-solid fa-pen"></i>
                        <i class="fa-solid fa-print"></i>
                        <i class="fa-solid fa-trash action-danger"></i>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="paginacion">
            <span>Mostrando 5 de 5 bienes</span>

            <div class="paginas">
                <button type="button">Anterior</button>
                <button type="button" class="pagina-activa">1</button>
                <button type="button">2</button>
                <button type="button">Siguiente</button>
            </div>
        </div>
    </div>
@endsection
