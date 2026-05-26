<?php

namespace App\Http\Controllers;

use App\Services\SOSService;
use Illuminate\Http\Request;

class SosCredentialController extends Controller
{
    public function index()
    {
        return view('sos.credentials', [
            'username'    => session('sos_username'),
            'hasPassword' => session()->has('sos_password'),
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        session([
            'sos_username' => $request->username,
            'sos_password' => $request->password,
        ]);

        return response()->json(['success' => true]);
    }

    public function test(Request $request)
    {
        if (!session()->has('sos_username') || !session()->has('sos_password')) {
            return response()->json([
                'success' => false,
                'message' => 'No hay credenciales guardadas. Guarde primero el usuario y contraseña.',
            ]);
        }

        try {
            $service = new SOSService();
            $ok = $service->login();

            return response()->json([
                'success' => $ok,
                'message' => $ok
                    ? 'Conexión exitosa. Las credenciales son válidas.'
                    : 'Login fallido. Verifique el usuario y contraseña.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar: ' . $e->getMessage(),
            ]);
        }
    }

    public function logout(Request $request)
    {
        session()->forget(['sos_username', 'sos_password']);

        return response()->json(['success' => true]);
    }
}
