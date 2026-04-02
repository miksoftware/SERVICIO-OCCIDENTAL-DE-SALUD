<?php

namespace App\Exports;

use App\Models\ConsultaResult;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResultsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private int $consultaId) {}

    public function query()
    {
        return ConsultaResult::query()->where('consulta_id', $this->consultaId)->orderBy('cedula');
    }

    public function headings(): array
    {
        return [
            'Cédula',
            'Tipo ID',
            'Primer Nombre',
            'Segundo Nombre',
            'Primer Apellido',
            'Segundo Apellido',
            'Fecha Nacimiento',
            'Género',
            'Parentesco',
            'Edad Años',
            'Edad Meses',
            'Edad Días',
            'Rango Salarial',
            'Plan',
            'Tipo Afiliado',
            'Inicio Vigencia',
            'Fin Vigencia',
            'IPS Primaria',
            'Semanas POS SOS',
            'Semanas POS Anterior',
            'Semanas PAC SOS',
            'Semanas PAC Anterior',
            'Estado',
            'Derecho',
            'Paga Cuota Moderadora',
            'Paga Copago',
            'Empleador Tipo ID',
            'Empleador NIT',
            'Empleador Razón Social',
            'Convenios',
            'Error',
        ];
    }

    public function map($row): array
    {
        $conveniosText = '';
        if ($row->convenios) {
            $parts = [];
            foreach ($row->convenios as $c) {
                $parts[] = ($c['estado'] ?? '') . ': ' . ($c['convenio'] ?? '');
            }
            $conveniosText = implode(' | ', $parts);
        }

        return [
            $row->cedula,
            $row->tipo_id,
            $row->primer_nombre,
            $row->segundo_nombre,
            $row->primer_apellido,
            $row->segundo_apellido,
            $row->fecha_nacimiento?->format('Y-m-d'),
            $row->genero,
            $row->parentesco,
            $row->edad_anos,
            $row->edad_meses,
            $row->edad_dias,
            $row->rango_salarial,
            $row->plan,
            $row->tipo_afiliado,
            $row->inicio_vigencia?->format('Y-m-d'),
            $row->fin_vigencia,
            $row->ips_primaria,
            $row->semanas_pos_sos,
            $row->semanas_pos_anterior,
            $row->semanas_pac_sos,
            $row->semanas_pac_anterior,
            $row->estado,
            $row->derecho,
            $row->paga_cuota_moderadora,
            $row->paga_copago,
            $row->empleador_tipo_id,
            $row->empleador_numero_id,
            $row->empleador_razon_social,
            $conveniosText,
            $row->error,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '003366'],
                ],
            ],
        ];
    }
}
