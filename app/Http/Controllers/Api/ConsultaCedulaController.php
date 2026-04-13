<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsultaResult;
use Illuminate\Http\JsonResponse;

class ConsultaCedulaController extends Controller
{
    /**
     * Retorna la información más reciente de un afiliado por cédula.
     *
     * GET /api/consulta/cedula/{cedula}
     */
    public function show(string $cedula): JsonResponse
    {
        $resultado = ConsultaResult::where('cedula', $cedula)
            ->whereNotNull('estado')
            ->where('error', null)
            ->latest('updated_at')
            ->first();

        if (! $resultado) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron resultados para la cédula proporcionada.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Consulta exitosa.',
            'data'    => [
                'cedula'             => $resultado->cedula,
                'tipo_id'            => $resultado->tipo_id,
                'primer_nombre'      => $resultado->primer_nombre,
                'segundo_nombre'     => $resultado->segundo_nombre,
                'primer_apellido'    => $resultado->primer_apellido,
                'segundo_apellido'   => $resultado->segundo_apellido,
                'nombre_completo'    => $resultado->nombre_completo,
                'fecha_nacimiento'   => $resultado->fecha_nacimiento?->toDateString(),
                'genero'             => $resultado->genero,
                'parentesco'         => $resultado->parentesco,
                'tipo_afiliado'      => $resultado->tipo_afiliado,
                'plan'               => $resultado->plan,
                'estado'             => $resultado->estado,
                'derecho'            => $resultado->derecho,
                'inicio_vigencia'    => $resultado->inicio_vigencia?->toDateString(),
                'fin_vigencia'       => $resultado->fin_vigencia,
                'ips_primaria'       => $resultado->ips_primaria,
                'consultado_en'      => $resultado->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
