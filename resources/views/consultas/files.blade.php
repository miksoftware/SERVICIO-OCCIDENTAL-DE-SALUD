@extends('layouts.app')
@section('title', 'Consultas Recientes - SOS Consultas')

@section('content')
<div class="glass-card">
    <h2>Consultas Recientes</h2>

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
                            <span class="badge {{ $c->status === 'completed' ? 'badge-success' : ($c->status === 'processing' ? 'badge-warning' : 'badge-info') }}">
                                {{ $c->status }}
                            </span>
                        </td>
                        <td>{{ $c->created_at->format('Y-m-d H:i') }}</td>
                        <td style="display: flex; gap: 0.3rem;">
                            @if($c->status === 'completed')
                                <a href="{{ route('consultas.show', $c) }}" class="btn btn-primary btn-sm">Ver</a>
                                <a href="{{ route('consultas.export', $c) }}" class="btn btn-success btn-sm">Excel</a>
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
