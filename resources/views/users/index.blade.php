@extends('layouts.app')
@section('title', 'Usuarios - SOS Consultas')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Gestión de Usuarios</h2>
        <button class="btn btn-primary" onclick="document.getElementById('modal-create').classList.add('active')">+ Nuevo Usuario</button>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="badge {{ $user->isAdmin() ? 'badge-admin' : 'badge-info' }}">{{ $user->role }}</span></td>
                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="openEdit({{ $user->id }}, '{{ e($user->name) }}', '{{ $user->email }}', '{{ $user->role }}')">Editar</button>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline" onsubmit="return confirm('¿Eliminar este usuario?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal-overlay" id="modal-create">
    <div class="modal-content">
        <h3>Nuevo Usuario</h3>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="role" class="form-control">
                    <option value="consulta">Consulta</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal-content">
        <h3>Editar Usuario</h3>
        <form method="POST" id="edit-form">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" id="edit-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit-email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Contraseña (dejar vacío para no cambiar)</label>
                <input type="password" name="password" class="form-control" minlength="6">
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="role" id="edit-role" class="form-control">
                    <option value="consulta">Consulta</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, name, email, role) {
    document.getElementById('edit-form').action = '/users/' + id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-role').value = role;
    document.getElementById('modal-edit').classList.add('active');
}
</script>
@endsection
