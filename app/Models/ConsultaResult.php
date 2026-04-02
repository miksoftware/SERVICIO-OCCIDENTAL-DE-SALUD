<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultaResult extends Model
{
    protected $fillable = [
        'consulta_id',
        'cedula',
        'tipo_id',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'fecha_nacimiento',
        'genero',
        'parentesco',
        'edad_anos',
        'edad_meses',
        'edad_dias',
        'rango_salarial',
        'plan',
        'tipo_afiliado',
        'inicio_vigencia',
        'fin_vigencia',
        'ips_primaria',
        'semanas_pos_sos',
        'semanas_pos_anterior',
        'semanas_pac_sos',
        'semanas_pac_anterior',
        'estado',
        'derecho',
        'paga_cuota_moderadora',
        'paga_copago',
        'empleador_tipo_id',
        'empleador_numero_id',
        'empleador_razon_social',
        'convenios',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'convenios' => 'array',
            'fecha_nacimiento' => 'date',
            'inicio_vigencia' => 'date',
        ];
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->primer_nombre} {$this->segundo_nombre} {$this->primer_apellido} {$this->segundo_apellido}");
    }
}
