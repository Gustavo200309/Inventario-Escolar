@extends('layouts.admin')

@section('title', 'Bienes Pendientes')

@section('content')
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.show { display: flex; justify-content: center; align-items: center; }
        .modal-content { background-color: #f7f8f6; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e8ede4; padding-bottom: 15px; }
        .modal-header h2 { margin: 0; color: #2f3e34; font-size: 22px; }
        .modal-header button { background: none; border: none; font-size: 24px; cursor: pointer; color: #6d746b; }
        .modal-body { margin-bottom: 20px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: #245c2d; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #d8ddd4; border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #2f943c; box-shadow: 0 0 0 3px rgba(47,148,60,0.1); }
        .modal-footer { display: flex; gap: 12px; justify-content: flex-end; padding-top: 15px; border-top: 1px solid #e8ede4; }
        .modal-footer button { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .btn-cancel { background: #e8ede4; color: #2f3e34; }
        .btn-cancel:hover { background: #dae2d6; }
        .btn-submit { background: #2f943c; color: white; }
        .btn-submit:hover { background: #21692c; transform: translateY(-2px); }
        .info-text { color: #666; margin: 10px 0; }
    </style>

    <div class="header">
        <div>
            <h1>Bienes Pendientes</h1>
            <p>Gestiona los bienes que requieren atenci&oacute;n</p>
        </div>
    </div>

    <div class="stats">
        <article class="stat-card">
            <h3>Total pendientes</h3>
            <span class="green">{{ count($pendientes ?? []) }}</span>
        </article>

        <article class="stat-card">
            <h3>Prioridad alta</h3>
            <span class="red">{{ collect($pendientes ?? [])->filter(fn($p) => $p->prioridad === 'Alta')->count() }}</span>
        </article>

        <article class="stat-card">
            <h3>Sin asignar</h3>
            <span class="green">{{ collect($pendientes ?? [])->filter(fn($p) => $p->razon === 'Sin asignar')->count() }}</span>
        </article>
    </div>

    <div class="search-box">
        <form method="GET" class="search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Buscar bienes pendientes..." value="{{ $search ?? '' }}">
            <select name="prioridad">
                <option value="">Todas las prioridades</option>
                <option value="Alta" {{ request('prioridad') === 'Alta' ? 'selected' : '' }}>Alta</option>
                <option value="Media" {{ request('prioridad') === 'Media' ? 'selected' : '' }}>Media</option>
                <option value="Baja" {{ request('prioridad') === 'Baja' ? 'selected' : '' }}>Baja</option>
            </select>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </form>
    </div>

    @forelse($pendientes as $bien)
        <article class="item">
            <div class="priority {{ strtolower($bien->prioridad ?? 'media') }}">{{ $bien->prioridad ?? 'Media' }}</div>

            <div class="item-top">
                <div class="alert-icon">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>

                <div class="item-info">
                    <h3>{{ $bien->nombre_bien }}</h3>
                    <div class="code">{{ $bien->no_inventario }}</div>

                    <div class="details">
                        <div>
                            <p>Raz&oacute;n</p>
                            <strong>{{ $bien->razon ?? 'N/A' }}</strong>
                        </div>

                        <div>
                            <p>Estado</p>
                            <strong>{{ $bien->estatus ?? 'Pendiente' }}</strong>
                        </div>

                        <div>
                            <p>Fecha de registro</p>
                            <strong>{{ $bien->created_at ? \Carbon\Carbon::parse($bien->created_at)->format('d/m/Y') : 'N/A' }}</strong>
                        </div>
                    </div>

                    <div class="buttons">
                        <button type="button" class="btn btn-outline" onclick="viewDetailsPendiente({{ $bien->id_bien }})">
                            <i class="fa-solid fa-eye"></i>
                            Ver detalles
                        </button>

                        @if(Auth::user()->isAdmin())
                            <button type="button" class="btn btn-green" onclick="openModalResolver({{ $bien->id_bien }})">
                                <i class="fa-solid fa-pen"></i>
                                Resolver
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    @empty
        <p style="text-align: center; padding: 40px;">No hay bienes pendientes registrados</p>
    @endforelse

    <!-- Modal Resolver Pendiente -->
    <div id="modalResolver" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Resolver bien pendiente</h2>
                <button onclick="closeModalResolver()">&times;</button>
            </div>
            <form id="formResolver" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p class="info-text">Selecciona cómo resolver este bien:</p>
                    <div class="form-group">
                        <label for="accion">Acción a realizar *</label>
                        <select id="accion" name="accion" required>
                            <option value="">Seleccionar acción</option>
                            <option value="Asignar">Asignar a personal</option>
                            <option value="Mantenimiento">Enviar a mantenimiento</option>
                            <option value="Reparar">Reparar</option>
                            <option value="Descartar">Descartar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notas">Notas (opcional)</label>
                        <textarea id="notas" name="notas" rows="4" placeholder="Ingresa notas sobre la acción realizada..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="nuevo_estatus">Nuevo estado *</label>
                        <select id="nuevo_estatus" name="nuevo_estatus" required>
                            <option value="">Seleccionar estado</option>
                            <option value="Resuelto">Resuelto</option>
                            <option value="En revisión">En revisión</option>
                            <option value="En mantenimiento">En mantenimiento</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModalResolver()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewDetailsPendiente(id) {
            alert('Ver detalles del bien #' + id + ' (por implementar)');
        }

        function openModalResolver(id) {
            document.getElementById('modalResolver').classList.add('show');
            document.getElementById('formResolver').action = '/admin/bienes/' + id + '/resolver';
        }

        function closeModalResolver() {
            document.getElementById('modalResolver').classList.remove('show');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalResolver');
            if (event.target === modal) {
                closeModalResolver();
            }
        }
    </script>
@endsection
