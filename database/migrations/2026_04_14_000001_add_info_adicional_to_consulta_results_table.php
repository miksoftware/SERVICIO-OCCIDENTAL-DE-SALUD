<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulta_results', function (Blueprint $table) {
            $table->string('estado_civil', 50)->nullable()->after('empleador_razon_social');
            $table->string('telefono', 50)->nullable()->after('estado_civil');
            $table->string('direccion', 255)->nullable()->after('telefono');
            $table->string('barrio', 100)->nullable()->after('direccion');
            $table->string('ciudad_residencia', 255)->nullable()->after('barrio');
            $table->string('departamento', 100)->nullable()->after('ciudad_residencia');
            $table->integer('semanas_cotizadas')->nullable()->after('departamento');
            $table->string('afp', 255)->nullable()->after('semanas_cotizadas');
        });
    }

    public function down(): void
    {
        Schema::table('consulta_results', function (Blueprint $table) {
            $table->dropColumn([
                'estado_civil',
                'telefono',
                'direccion',
                'barrio',
                'ciudad_residencia',
                'departamento',
                'semanas_cotizadas',
                'afp',
            ]);
        });
    }
};
