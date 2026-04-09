@extends('layouts.app')
@section('title', 'Consultas Recientes - SOS Consultas')

@section('content')
<div class="glass-card">
    <h2>Consultas Recientes</h2>

    @if(session('success'))
        <div style="background: rgba(105, 240, 174, 0.15); border: 1px solid #69f0ae; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; color: #69f0ae; font-size: 0.9rem;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background: rgba(255, 107, 122, 0.15); border: 1px solid #ff6b7a; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; color: #ff6b7a; font-size: 0.9rem;">
            {{ session('error') }}
        </div>
    @endif

    @if($consultas->isEmpty())
        <p style="color: #7777aa; font-size: 0.9rem;">No hay consultas registradas aún.</p>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Archivo</th>
                        <th>Usuario</th>
                        <th>Total Cédulas</th>
                        <th>Procesadas</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($consultas as $c)
                    <tr>
                        <td>{{ $c->id }}</td>
                        <td>{{ $c->filename }}</td>
                        <td>{{ $c->user->name }}</td>
                        <td>{{ $c->total_cedulas }}</td>
                        <td>{{ $c->processed }}</td>
                        <td>
                            <span class="badge {{ $c->status === 'completed' ? 'badge-success' : ($c->status === 'failed' ? 'badge-danger' : ($c->status === 'processing' ? 'badge-warning' : 'badge-info')) }}">
                                {{ $c->status }}
                            </span>
                        </td>
                        <td>{{ $c->created_at->format('Y-m-d H:i') }}</td>
                        <td style="display: flex; gap: 0.3rem; flex-wrap: wrap; position: relative;">
                            @if($c->status === 'completed')
                                <a href="{{ route('consultas.show', $c) }}" class="btn btn-primary btn-sm">Ver</a>
                                <a href="{{ route('consultas.export', $c) }}" class="btn btn-success btn-sm">Excel</a>
                                @if($c->results()->whereNotNull('error')->exists())
                                    <form action="{{ route('consultas.retry-failed', $c) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Reintentar solo las cédulas con error?')">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm">Reintentar fallidas</button>
                                    </form>
                                @endif
                            @endif
                            @if(in_array($c->status, ['processing', 'failed']))
                                <form action="{{ route('consultas.retry', $c) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Reintentar toda la consulta? Se eliminarán los resultados parciales.')">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm">Reintentar</button>
                                </form>
                                @if($c->error_message)
                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">Ver Error</button>
                                    <div style="display:none; position:absolute; z-index:50; background:rgba(20,20,50,0.95); border:1px solid #ff6b7a; border-radius:8px; padding:0.8rem; max-width:400px; font-size:0.8rem; color:#ff6b7a; margin-top:0.3rem; word-break:break-word; right:0; top:100%;">
                                        {{ $c->error_message }}
                                        <button type="button" onclick="this.parentElement.style.display='none'" style="display:block; margin-top:0.5rem; background:none; border:1px solid #ff6b7a; color:#ff6b7a; border-radius:4px; padding:2px 8px; cursor:pointer; font-size:0.75rem;">Cerrar</button>
                                    </div>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($consultas->hasPages())
        <div class="pagination">
            {{ $consultas->links() }}
        </div>
        @endif
    @endif
</div>
@endsection
