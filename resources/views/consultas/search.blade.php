@extends('layouts.app')
@section('title', 'Buscar Cédula - SOS Consultas')

@section('content')
<div class="glass-card">
    <h2>Buscar por Cédula</h2>
    <form method="GET" action="{{ route('consultas.search') }}" class="form-inline" style="margin-top: 1rem;">
        <div class="form-group" style="flex: 1;">
            <label>Número de Cédula</label>
            <input type="text" name="cedula" class="form-control" placeholder="Ingrese número de cédula..." value="{{ $cedula ?? '' }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
</div>

@if(isset($results))
    @if($results->isEmpty())
        <div class="glass-card">
            <div class="alert alert-info" style="margin-bottom: 0;">No se encontraron resultados para la cédula <strong>{{ $cedula }}</strong></div>
        </div>
    @else
        <div class="glass-card">
            <h2>Resultados para: {{ $cedula }} <span class="badge badge-info">{{ $results->count() }} resultado(s)</span></h2>

            @foreach($results as $r)
            <div class="result-item {{ $r->error ? 'error' : '' }}" style="margin-top: 1rem;">
                @if($r->error)
                    <div class="result-header">
                        <span class="cedula-label">{{ $r->cedula }}</span>
                        <span class="badge badge-danger">Error</span>
                    </div>
                    <div style="color: #ff6b7a; font-size: 0.85rem;">{{ $r->error }}</div>
                @else
                    <div class="result-header">
                        <span class="cedula-label">{{ $r->cedula }} — {{ $r->nombre_completo }}</span>
                        <span class="badge {{ $r->estado === 'ACTIVO' ? 'badge-success' : 'badge-warning' }}">{{ $r->estado }}</span>
                    </div>
                    <div class="result-grid" style="margin-top: 0.5rem;">
                        <div class="field"><span class="field-label">Tipo ID: </span><span class="field-value">{{ $r->tipo_id }}</span></div>
                        <div class="field"><span class="field-label">Fecha Nacimiento: </span><span class="field-value">{{ $r->fecha_nacimiento?->format('Y-m-d') }}</span></div>
                        <div class="field"><span class="field-label">Género: </span><span class="field-value">{{ $r->genero }}</span></div>
                        <div class="field"><span class="field-label">Parentesco: </span><span class="field-value">{{ $r->parentesco }}</span></div>
                        <div class="field"><span class="field-label">Edad: </span><span class="field-value">{{ $r->edad_anos }} años, {{ $r->edad_meses }} meses, {{ $r->edad_dias }} días</span></div>
                        <div class="field"><span class="field-label">Rango Salarial: </span><span class="field-value">{{ $r->rango_salarial }}</span></div>
                        <div class="field"><span class="field-label">Plan: </span><span class="field-value">{{ $r->plan }}</span></div>
                        <div class="field"><span class="field-label">Tipo Afiliado: </span><span class="field-value">{{ $r->tipo_afiliado }}</span></div>
                        <div class="field"><span class="field-label">Inicio Vigencia: </span><span class="field-value">{{ $r->inicio_vigencia?->format('Y-m-d') }}</span></div>
                        <div class="field"><span class="field-label">Fin Vigencia: </span><span class="field-value">{{ $r->fin_vigencia }}</span></div>
                        <div class="field"><span class="field-label">IPS Primaria: </span><span class="field-value">{{ $r->ips_primaria }}</span></div>
                        <div class="field"><span class="field-label">Semanas POS SOS: </span><span class="field-value">{{ $r->semanas_pos_sos }}</span></div>
                        <div class="field"><span class="field-label">Semanas POS Ant: </span><span class="field-value">{{ $r->semanas_pos_anterior }}</span></div>
                        <div class="field"><span class="field-label">Semanas PAC SOS: </span><span class="field-value">{{ $r->semanas_pac_sos }}</span></div>
                        <div class="field"><span class="field-label">Semanas PAC Ant: </span><span class="field-value">{{ $r->semanas_pac_anterior }}</span></div>
                        <div class="field"><span class="field-label">Derecho: </span><span class="field-value">{{ $r->derecho }}</span></div>
                        <div class="field"><span class="field-label">Cuota Moderadora: </span><span class="field-value">{{ $r->paga_cuota_moderadora }}</span></div>
                        <div class="field"><span class="field-label">Copago: </span><span class="field-value">{{ $r->paga_copago }}</span></div>
                    </div>

                    @if($r->empleador_razon_social)
                    <div style="margin-top: 0.8rem; padding-top: 0.8rem; border-top: 1px solid rgba(255,255,255,0.06);">
                        <h3 style="font-size: 0.9rem; color: #7777aa; margin-bottom: 0.4rem;">Empleador</h3>
                        <div class="result-grid">
                            <div class="field"><span class="field-label">Tipo ID: </span><span class="field-value">{{ $r->empleador_tipo_id }}</span></div>
                            <div class="field"><span class="field-label">NIT: </span><span class="field-value">{{ $r->empleador_numero_id }}</span></div>
                            <div class="field"><span class="field-label">Razón Social: </span><span class="field-value">{{ $r->empleador_razon_social }}</span></div>
                        </div>
                    </div>
                    @endif

                    @if($r->convenios && count($r->convenios) > 0)
                    <div style="margin-top: 0.8rem; padding-top: 0.8rem; border-top: 1px solid rgba(255,255,255,0.06);">
                        <h3 style="font-size: 0.9rem; color: #7777aa; margin-bottom: 0.4rem;">Convenios de Capitación</h3>
                        @foreach($r->convenios as $conv)
                            <div class="field" style="margin-bottom: 0.3rem;">
                                <span class="badge badge-info" style="margin-right: 0.5rem;">{{ $conv['estado'] }}</span>
                                <span class="field-value">{{ $conv['convenio'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    @endif

                    <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #555580;">
                        Consultado: {{ $r->created_at->format('Y-m-d H:i') }} | Lote #{{ $r->consulta_id }}
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    @endif
@endif
@endsection
