<?php

namespace App\Http\Controllers;

use App\Exports\ResultsExport;
use App\Imports\CedulasImport;
use App\Models\Consulta;
use App\Models\ConsultaResult;
use App\Services\SOSService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ConsultaController extends Controller
{
    public function index()
    {
        $consultas = Consulta::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('consultas.index', compact('consultas'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $import = new CedulasImport();
        Excel::import($import, $file);

        $cedulas = $import->getCedulas();

        if (empty($cedulas)) {
            return back()->with('error', 'No se encontraron cédulas válidas en el archivo.');
        }

        $consulta = Consulta::create([
            'user_id'       => auth()->id(),
            'filename'      => $file->getClientOriginalName(),
            'total_cedulas'  => count($cedulas),
            'cedulas'        => $cedulas,
            'processed'     => 0,
            'status'        => 'pending',
        ]);

        return response()->json([
            'consulta_id'   => $consulta->id,
            'total_cedulas' => count($cedulas),
            'cedulas'       => $cedulas,
        ]);
    }

    /**
     * Process all cedulas in a single request using SSE (Server-Sent Events).
     * This keeps ONE SOSService instance alive for the entire batch, reusing
     * the same authenticated session and cookie jar for all cedulas.
     * ONE login per file upload, no matter how many cedulas.
     */
    public function processBatch(Request $request)
    {
        $request->validate([
            'consulta_id' => 'required|integer|exists:consultas,id',
        ]);

        $consulta = Consulta::findOrFail($request->consulta_id);

        if ($consulta->user_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Prevent duplicate runs
        if ($consulta->status === 'processing') {
            return response()->json(['error' => 'Esta consulta ya se está procesando'], 409);
        }
        if ($consulta->status === 'completed') {
            return response()->json(['error' => 'Esta consulta ya fue procesada'], 409);
        }

        $cedulas = $consulta->cedulas ?? [];

        if (empty($cedulas)) {
            return response()->json(['error' => 'No hay cédulas para procesar'], 400);
        }

        // Mark as processing immediately
        $consulta->update(['status' => 'processing', 'processed' => 0]);

        // Release session lock so browser isn't blocked
        session()->save();

        // Increase execution time for large batches
        set_time_limit(count($cedulas) * 30);

        return response()->stream(function () use ($consulta, $cedulas) {
            // Disable output buffering for SSE
            if (ob_get_level()) ob_end_clean();

            // === SINGLE SOSService instance for ALL cedulas ===
            // This means ONE login, ONE session, ONE cookie jar for the entire batch
            $sosService = new SOSService();
            \Log::channel('sos')->info("SOS BATCH: Iniciando lote de " . count($cedulas) . " cédulas con UNA sola sesión");

            foreach ($cedulas as $index => $cedula) {
                $data = $sosService->consultarCedula($cedula);

                // Save result
                $resultData = array_merge(
                    ['consulta_id' => $consulta->id, 'cedula' => $cedula],
                    $this->sanitizeResultData($data)
                );
                $result = ConsultaResult::create($resultData);

                // Update progress
                $processed = $index + 1;
                $isLast = ($processed >= count($cedulas));

                $consulta->update([
                    'processed' => $processed,
                    'status'    => $isLast ? 'completed' : 'processing',
                ]);

                // Send SSE event with event ID for tracking
                $eventData = json_encode([
                    'success'   => !isset($data['error']),
                    'result'    => $result,
                    'processed' => $processed,
                    'total'     => count($cedulas),
                ]);

                echo "id: {$processed}\ndata: {$eventData}\n\n";

                if (connection_aborted()) {
                    \Log::channel('sos')->info("SOS BATCH: Conexión abortada en cédula {$processed}/" . count($cedulas));
                    break;
                }
                flush();
            }

            \Log::channel('sos')->info("SOS BATCH: Lote completado - " . count($cedulas) . " cédulas procesadas con UNA sesión");
            echo "event: done\ndata: {}\n\n";
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function search(Request $request)
    {
        $results = null;
        $cedula = $request->get('cedula');

        if ($cedula) {
            $results = ConsultaResult::where('cedula', $cedula)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('consultas.search', compact('results', 'cedula'));
    }

    public function files()
    {
        $consultas = Consulta::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('consultas.files', compact('consultas'));
    }

    public function export(Consulta $consulta)
    {
        $filename = 'resultados_' . str_replace([' ', '.'], '_', $consulta->filename) . '_' . $consulta->created_at->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new ResultsExport($consulta->id), $filename);
    }

    public function show(Consulta $consulta)
    {
        $results = $consulta->results()->orderBy('cedula')->get();
        return view('consultas.show', compact('consulta', 'results'));
    }

    private function sanitizeResultData(array $data): array
    {
        $fields = [
            'cedula', 'tipo_id', 'primer_nombre', 'segundo_nombre',
            'primer_apellido', 'segundo_apellido', 'fecha_nacimiento',
            'genero', 'parentesco', 'edad_anos', 'edad_meses', 'edad_dias',
            'rango_salarial', 'plan', 'tipo_afiliado', 'inicio_vigencia',
            'fin_vigencia', 'ips_primaria', 'semanas_pos_sos',
            'semanas_pos_anterior', 'semanas_pac_sos', 'semanas_pac_anterior',
            'estado', 'derecho', 'paga_cuota_moderadora', 'paga_copago',
            'empleador_tipo_id', 'empleador_numero_id', 'empleador_razon_social',
            'convenios', 'error',
        ];

        $sanitized = [];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = is_string($data[$field]) ? strip_tags($data[$field]) : $data[$field];
            }
        }

        return $sanitized;
    }
}
