<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class CedulasImport implements ToCollection, WithHeadingRow
{
    private array $cedulas = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Try common column names
            $cedula = $row['cedula']
                ?? $row['numero_documento']
                ?? $row['identificacion']
                ?? $row['documento']
                ?? $row['num_documento']
                ?? $row['cc']
                ?? $row['nro_documento']
                ?? $row->first();

            if ($cedula) {
                $cedula = trim((string) $cedula);
                // Remove decimals from numeric values (e.g., 1107057896.0)
                $cedula = preg_replace('/\.0+$/', '', $cedula);
                if (is_numeric($cedula) && strlen($cedula) >= 5 && strlen($cedula) <= 15) {
                    $this->cedulas[] = $cedula;
                }
            }
        }

        $this->cedulas = array_unique($this->cedulas);
    }

    public function getCedulas(): array
    {
        return array_values($this->cedulas);
    }
}
