<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsultaResult;
use Illuminate\Http\JsonResponse;

class ConsultaCedulaController extends Controller
{
    /**
     * Retorna el historial completo de consultas de un afiliado por cédula,
     * ordenado del más reciente al más antiguo.
     *
     * GET /api/consulta/cedula/{cedula}
     */
    public function show(string $cedula): JsonResponse
    {
        $resultados = ConsultaResult::where('cedula', $cedula)
            ->whereNotNull('estado')
            ->where('error', null)
            ->latest('updated_at')
            ->get();

        if ($resultados->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron resultados para la cédula proporcionada.',
                'data'    => null,
            ], 404);
        }

        $data = $resultados->map(fn (ConsultaResult $r) => [
            'cedula'              => $r->cedula,
            'tipo_id'             => $r->tipo_id,
            'primer_nombre'       => $r->primer_nombre,
            'segundo_nombre'      => $r->segundo_nombre,
            'primer_apellido'     => $r->primer_apellido,
            'segundo_apellido'    => $r->segundo_apellido,
            'nombre_completo'     => $r->nombre_completo,
            'fecha_nacimiento'    => $r->fecha_nacimiento?->toDateString(),
            'genero'              => $r->genero,
            'parentesco'          => $r->parentesco,
            'edad_anos'           => $r->edad_anos,
            'edad_meses'          => $r->edad_meses,
            'edad_dias'           => $r->edad_dias,
            'rango_salarial'      => $r->rango_salarial,
            'tipo_afiliado'       => $r->tipo_afiliado,
            'plan'                => $r->plan,
            'estado'              => $r->estado,
            'derecho'             => $r->derecho,
            'inicio_vigencia'     => $r->inicio_vigencia?->toDateString(),
            'fin_vigencia'        => $r->fin_vigencia,
            'ips_primaria'        => $r->ips_primaria,
            'semanas_pos_sos'     => $r->semanas_pos_sos,
            'semanas_pos_anterior' => $r->semanas_pos_anterior,
            'semanas_pac_sos'     => $r->semanas_pac_sos,
            'semanas_pac_anterior' => $r->semanas_pac_anterior,
            'paga_cuota_moderadora' => $r->paga_cuota_moderadora,
            'paga_copago'         => $r->paga_copago,
            'empleador' => [
                'tipo_id'       => $r->empleador_tipo_id,
                'numero_id'     => $r->empleador_numero_id,
                'razon_social'  => $r->empleador_razon_social,
            ],
            'informacion_adicional' => [
                'estado_civil'       => $r->estado_civil,
                'telefono'           => $r->telefono,
                'direccion'          => $r->direccion,
                'barrio'             => $r->barrio,
                'ciudad_residencia'  => $r->ciudad_residencia,
                'departamento'       => $r->departamento,
                'semanas_cotizadas'  => $r->semanas_cotizadas,
                'afp'                => $r->afp,
            ],
            'consultado_en' => $r->updated_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consulta exitosa.',
            'total'   => $data->count(),
            'data'    => $data,
        ]);
    }
}
