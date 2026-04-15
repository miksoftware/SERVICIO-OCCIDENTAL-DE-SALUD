@extends('layouts.app')
@section('title', 'Procesar Consultas - SOS Consultas')

@section('content')
<div class="glass-card">
    <h2>Procesar Consultas Masivas</h2>

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

    <p style="color: #7777aa; font-size: 0.85rem; margin-bottom: 1.5rem;">
        Suba un archivo Excel o CSV con cédulas para consultar en el portal SOS. La primera columna o una columna llamada "cedula" será utilizada.
    </p>

    <form id="upload-form" enctype="multipart/form-data">
        @csrf
        <div class="file-upload" id="drop-zone" onclick="document.getElementById('file-input').click()">
            <div class="icon">📄</div>
            <p>Haga clic o arrastre un archivo aquí</p>
            <p style="font-size: 0.8rem; color: #555580;">.xlsx, .xls, .csv — Máx 5MB</p>
            <div class="filename" id="file-name"></div>
            <input type="file" id="file-input" name="file" accept=".xlsx,.xls,.csv">
        </div>
        <div style="margin-top: 1rem; display: flex; gap: 1rem; align-items: center;">
            <button type="submit" class="btn btn-primary" id="btn-upload" disabled>Subir y Procesar</button>
            <span id="upload-status" style="font-size: 0.85rem; color: #7777aa;"></span>
        </div>
    </form>
</div>

<!-- Progress section (hidden initially) -->
<div class="glass-card" id="progress-section" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Procesando...</h2>
        <span id="progress-text" style="font-size: 0.9rem; color: #00b4d8;">0 / 0</span>
    </div>
    <div class="progress-container">
        <div class="progress-bar" id="progress-bar" style="width: 0%;">0%</div>
    </div>
    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
        <span style="font-size: 0.85rem;">
            <span style="color: #69f0ae;">●</span> Exitosas: <strong id="count-success">0</strong>
        </span>
        <span style="font-size: 0.85rem;">
            <span style="color: #ff6b7a;">●</span> Errores: <strong id="count-errors">0</strong>
        </span>
    </div>
</div>

<!-- Results section -->
<div class="glass-card" id="results-section" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Resultados</h2>
        <a class="btn btn-success btn-sm" id="btn-export" href="#" style="display: none;">Exportar Excel</a>
    </div>
    <div id="results-container"></div>
</div>

<!-- Recent batches -->
<div class="glass-card">
    <h2>Consultas Recientes</h2>
    @if($consultas->isEmpty())
        <p style="color: #7777aa; font-size: 0.9rem;">No hay consultas recientes.</p>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Total</th>
                        <th>Procesadas</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($consultas as $c)
                    <tr>
                        <td>{{ $c->filename }}</td>
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
                                @if(auth()->user()->role === 'admin' && $c->results()->whereNotNull('error')->exists())
                                    <form action="{{ route('consultas.retry-failed', $c) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Reintentar solo las cédulas con error?')">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm">Reintentar fallidas</button>
                                    </form>
                                @endif
                            @endif
                            @if(in_array($c->status, ['pending', 'processing', 'failed']))
                                <a href="{{ route('consultas.show', $c) }}" class="btn btn-primary btn-sm">Ver</a>
                                @if(auth()->user()->role === 'admin')
                                <form action="{{ route('consultas.retry', $c) }}" method="POST" style="display:inline;" onsubmit="return confirm('{{ $c->status === 'pending' ? '¿Procesar esta consulta ahora?' : '¿Reintentar toda la consulta? Se eliminarán los resultados parciales.' }}')">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm">{{ $c->status === 'pending' ? 'Procesar' : 'Reintentar' }}</button>
                                </form>
                                @endif
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
    @endif
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let consultaId = null;
let cedulasList = [];
let currentIndex = 0;
let successCount = 0;
let errorCount = 0;
let totalToProcess = 0;

// File upload drag & drop
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const fileName = document.getElementById('file-name');
const btnUpload = document.getElementById('btn-upload');

['dragenter', 'dragover'].forEach(e => {
    dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.style.borderColor = '#00b4d8'; });
});
['dragleave', 'drop'].forEach(e => {
    dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.style.borderColor = ''; });
});
dropZone.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (files.length) { fileInput.files = files; updateFileName(); }
});
fileInput.addEventListener('change', updateFileName);

function updateFileName() {
    if (fileInput.files.length) {
        fileName.textContent = fileInput.files[0].name;
        btnUpload.disabled = false;
    }
}

// Upload form
document.getElementById('upload-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    btnUpload.disabled = true;
    document.getElementById('upload-status').textContent = 'Subiendo archivo...';

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('_token', csrfToken);

    try {
        const res = await fetch('{{ route("consultas.upload") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });

        if (!res.ok) {
            const err = await res.json();
            throw new Error(err.message || 'Error al subir archivo');
        }

        const data = await res.json();
        consultaId = data.consulta_id;
        cedulasList = data.cedulas;
        currentIndex = 0;
        successCount = 0;
        errorCount = 0;

        document.getElementById('upload-status').textContent = `${data.total_cedulas} cédulas encontradas. Procesando...`;
        document.getElementById('progress-section').style.display = 'block';
        document.getElementById('results-section').style.display = 'block';
        document.getElementById('progress-text').textContent = `0 / ${cedulasList.length}`;
        document.getElementById('results-container').innerHTML = '';

        processBatch();
    } catch(err) {
        document.getElementById('upload-status').textContent = 'Error: ' + err.message;
        btnUpload.disabled = false;
    }
});

function processBatch() {
    // Use fetch + ReadableStream to process all cedulas via streaming POST
    // This sends cookies/CSRF properly (unlike EventSource which uses GET)
    const url = `{{ route("consultas.process-batch") }}`;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'text/event-stream',
        },
        body: JSON.stringify({ consulta_id: consultaId }),
    }).then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        function read() {
            reader.read().then(({ done, value }) => {
                if (done) {
                    finishBatch();
                    return;
                }

                buffer += decoder.decode(value, { stream: true });

                // Parse SSE lines from buffer
                const lines = buffer.split('\n');
                buffer = lines.pop(); // keep incomplete line in buffer

                let eventType = 'message';
                for (const line of lines) {
                    if (line.startsWith('event: ')) {
                        eventType = line.substring(7).trim();
                    } else if (line.startsWith('data: ')) {
                        const jsonStr = line.substring(6);
                        if (eventType === 'done') {
                            finishBatch();
                            return;
                        }
                        if (eventType === 'error') {
                            try {
                                const errData = JSON.parse(jsonStr);
                                document.getElementById('upload-status').innerHTML = `<span style="color:#ff6b7a;">Error: ${errData.error}</span>`;
                            } catch(e) {
                                document.getElementById('upload-status').innerHTML = '<span style="color:#ff6b7a;">Error desconocido en el servidor</span>';
                            }
                            btnUpload.disabled = false;
                            return;
                        }
                        try {
                            const data = JSON.parse(jsonStr);
                            if (data.total && !totalToProcess) {
                                totalToProcess = data.total;
                            }
                            if (data.success) {
                                successCount++;
                                appendResult(data.result, false);
                            } else {
                                errorCount++;
                                appendResult(data.result, true);
                            }
                            currentIndex = data.processed;
                            updateProgress();
                        } catch(e) {
                            console.error('Parse error:', e, jsonStr);
                        }
                        eventType = 'message';
                    }
                }

                read();
            }).catch(err => {
                console.error('Stream read error:', err);
                const total = totalToProcess || cedulasList.length;
                if (currentIndex >= total) {
                    finishBatch();
                } else {
                    document.getElementById('upload-status').textContent = `Error de conexión. Se procesaron ${currentIndex} de ${total} cédulas.`;
                    btnUpload.disabled = false;
                }
            });
        }

        read();
    }).catch(err => {
        console.error('Fetch error:', err);
        document.getElementById('upload-status').textContent = `Error de conexión: ${err.message}`;
        btnUpload.disabled = false;
    });
}

function finishBatch() {
    document.getElementById('upload-status').textContent = 'Procesamiento completado.';
    document.getElementById('btn-export').style.display = 'inline-flex';
    document.getElementById('btn-export').href = '/consultas/' + consultaId + '/export';
    btnUpload.disabled = false;
}

function updateProgress() {
    const total = totalToProcess || cedulasList.length;
    const pct = total > 0 ? Math.round((currentIndex / total) * 100) : 0;
    document.getElementById('progress-bar').style.width = pct + '%';
    document.getElementById('progress-bar').textContent = pct + '%';
    document.getElementById('progress-text').textContent = `${currentIndex} / ${total}`;
    document.getElementById('count-success').textContent = successCount;
    document.getElementById('count-errors').textContent = errorCount;
}

function appendResult(r, isError) {
    const container = document.getElementById('results-container');
    const div = document.createElement('div');
    div.className = 'result-item' + (isError ? ' error' : '');

    if (isError || r.error) {
        div.innerHTML = `
            <div class="result-header">
                <span class="cedula-label">${r.cedula}</span>
                <span class="badge badge-danger">Error</span>
            </div>
            <div style="color: #ff6b7a; font-size: 0.85rem;">${r.error || 'Error desconocido'}</div>
        `;
    } else {
        const nombre = [r.primer_nombre, r.segundo_nombre, r.primer_apellido, r.segundo_apellido].filter(Boolean).join(' ');
        div.innerHTML = `
            <div class="result-header">
                <span class="cedula-label">${r.cedula}</span>
                <span class="badge badge-success">${r.estado || 'OK'}</span>
            </div>
            <div class="result-grid">
                <div class="field"><span class="field-label">Nombre: </span><span class="field-value">${nombre}</span></div>
                <div class="field"><span class="field-label">Tipo Afiliado: </span><span class="field-value">${r.tipo_afiliado || '-'}</span></div>
                <div class="field"><span class="field-label">IPS Primaria: </span><span class="field-value">${r.ips_primaria || '-'}</span></div>
                <div class="field"><span class="field-label">Estado: </span><span class="field-value">${r.estado || '-'}</span></div>
                <div class="field"><span class="field-label">Empleador: </span><span class="field-value">${r.empleador_razon_social || '-'}</span></div>
                <div class="field"><span class="field-label">Derecho: </span><span class="field-value">${r.derecho || '-'}</span></div>
            </div>
        `;
    }

    container.prepend(div);
}

// Auto-retry: if redirected with auto_retry param, start processing automatically
(function() {
    const params = new URLSearchParams(window.location.search);
    const autoRetryId = params.get('auto_retry');
    if (!autoRetryId) return;

    // Clean URL without reloading
    window.history.replaceState({}, '', window.location.pathname);

    consultaId = parseInt(autoRetryId);
    currentIndex = 0;
    successCount = 0;
    errorCount = 0;

    // Show progress UI
    document.getElementById('upload-status').textContent = 'Reintentando consulta #' + consultaId + '...';
    document.getElementById('progress-section').style.display = 'block';
    document.getElementById('results-section').style.display = 'block';
    document.getElementById('results-container').innerHTML = '';
    btnUpload.disabled = true;

    // We don't know cedulasList length yet, the SSE will tell us via total
    totalToProcess = 0;
    document.getElementById('progress-text').textContent = 'Iniciando...';

    processBatch();
})();
</script>
@endsection
