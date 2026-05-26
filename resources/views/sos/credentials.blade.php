@extends('layouts.app')
@section('title', 'Credenciales SOS')

@section('content')
<div class="glass-card" style="max-width: 580px;">
    <h2>Credenciales del Portal SOS</h2>
    <p style="color: #7777aa; font-size: 0.85rem; margin-bottom: 1.5rem;">
        Ingrese el usuario y contraseña con los que accede al portal
        <strong style="color: #00b4d8;">centralaplicaciones.sos.com.co</strong>.
        Las credenciales se guardan en la sesión y se usan automáticamente en todas las consultas.
    </p>

    {{-- Estado actual --}}
    @if($username)
    <div id="statusConnected" style="background: rgba(0,200,83,0.1); border: 1px solid rgba(0,200,83,0.25); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="color: #69f0ae; font-weight: 600;">● Credenciales guardadas</span><br>
            <span style="color: #9999bb; font-size: 0.82rem;">Usuario: <strong style="color: #fff;">{{ $username }}</strong> &nbsp;·&nbsp; Contraseña: ••••••••</span>
        </div>
        <button class="btn btn-danger btn-sm" onclick="clearCredentials()">Limpiar</button>
    </div>
    @else
    <div id="statusEmpty" style="background: rgba(255,107,122,0.1); border: 1px solid rgba(255,107,122,0.25); border-radius: 10px; padding: 0.75rem 1rem; margin-bottom: 1.5rem;">
        <span style="color: #ff6b7a; font-size: 0.85rem;">⚠ Sin credenciales — las consultas no podrán autenticarse en el portal SOS.</span>
    </div>
    @endif

    {{-- Formulario --}}
    <div class="form-group">
        <label>Usuario</label>
        <input type="text" id="sosUsername" class="form-control"
               placeholder="Ej: ccfsantarosa"
               value="{{ $username ?? '' }}"
               autocomplete="off">
    </div>

    <div class="form-group">
        <label>Contraseña</label>
        <div style="position: relative;">
            <input type="password" id="sosPassword" class="form-control"
                   placeholder="{{ $hasPassword ? '(guardada — ingrese nueva para cambiar)' : 'Contraseña del portal SOS' }}"
                   autocomplete="new-password">
            <button type="button" onclick="togglePassword()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9999bb;font-size:0.85rem;">
                👁
            </button>
        </div>
    </div>

    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1.5rem;">
        <button class="btn btn-primary" id="btnSave" onclick="saveCredentials()">
            Guardar credenciales
        </button>
        <button class="btn btn-success" id="btnTest" onclick="testCredentials()" {{ !$username ? 'disabled' : '' }}>
            Probar conexión
        </button>
    </div>

    <div id="message" style="margin-top: 1rem; display: none;"></div>
</div>

{{-- Log de prueba --}}
<div class="glass-card" id="testLog" style="max-width: 580px; display: none;">
    <h3>Resultado de prueba</h3>
    <div id="testLogContent" style="font-family: Consolas, monospace; font-size: 0.82rem; color: #a0a0c0; white-space: pre-wrap;"></div>
</div>

@endsection

@section('scripts')
<script>
    function showMsg(text, type) {
        const el = document.getElementById('message');
        const colors = { success: '#69f0ae', error: '#ff6b7a', info: '#00b4d8' };
        el.style.display = 'block';
        el.innerHTML = `<div style="padding:0.75rem 1rem;border-radius:8px;background:rgba(255,255,255,0.05);border:1px solid ${colors[type]||colors.info}33;color:${colors[type]||colors.info};font-size:0.85rem;">${text}</div>`;
    }

    function togglePassword() {
        const inp = document.getElementById('sosPassword');
        inp.type = inp.type === 'password' ? 'text' : 'password';
    }

    async function saveCredentials() {
        const username = document.getElementById('sosUsername').value.trim();
        const password = document.getElementById('sosPassword').value;

        if (!username || !password) {
            showMsg('Complete usuario y contraseña.', 'error');
            return;
        }

        const btn = document.getElementById('btnSave');
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        try {
            const res = await fetch('{{ route("sos.credentials.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ username, password }),
            });

            const data = await res.json();

            if (data.success) {
                showMsg('✓ Credenciales guardadas correctamente.', 'success');
                document.getElementById('btnTest').disabled = false;
                setTimeout(() => location.reload(), 800);
            } else {
                showMsg('Error al guardar.', 'error');
            }
        } catch (e) {
            showMsg('Error de conexión: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Guardar credenciales';
        }
    }

    async function testCredentials() {
        const btn = document.getElementById('btnTest');
        btn.disabled = true;
        btn.textContent = '⏳ Probando conexión...';

        const logCard = document.getElementById('testLog');
        const logContent = document.getElementById('testLogContent');
        logCard.style.display = 'block';
        logContent.textContent = 'Conectando al portal SOS...\n(esto puede tardar hasta 15 segundos)';

        try {
            const res = await fetch('{{ route("sos.credentials.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            const data = await res.json();
            logContent.textContent = data.message;

            if (data.success) {
                logCard.style.borderColor = 'rgba(0,200,83,0.3)';
                showMsg('✓ ' + data.message, 'success');
            } else {
                logCard.style.borderColor = 'rgba(255,107,122,0.3)';
                showMsg('✗ ' + data.message, 'error');
            }
        } catch (e) {
            logContent.textContent = 'Error: ' + e.message;
            showMsg('Error de conexión: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Probar conexión';
        }
    }

    async function clearCredentials() {
        if (!confirm('¿Limpiar las credenciales guardadas?')) return;

        await fetch('{{ route("sos.credentials.logout") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });

        location.reload();
    }
</script>
@endsection
