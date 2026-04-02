<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulta_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained()->onDelete('cascade');
            $table->string('cedula', 20);
            $table->string('tipo_id', 10)->nullable();
            $table->string('primer_nombre', 100)->nullable();
            $table->string('segundo_nombre', 100)->nullable();
            $table->string('primer_apellido', 100)->nullable();
            $table->string('segundo_apellido', 100)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('genero', 5)->nullable();
            $table->string('parentesco', 50)->nullable();
            $table->integer('edad_anos')->nullable();
            $table->integer('edad_meses')->nullable();
            $table->integer('edad_dias')->nullable();
            $table->string('rango_salarial', 10)->nullable();
            $table->string('plan', 50)->nullable();
            $table->string('tipo_afiliado', 50)->nullable();
            $table->date('inicio_vigencia')->nullable();
            $table->string('fin_vigencia', 20)->nullable();
            $table->string('ips_primaria', 200)->nullable();
            $table->integer('semanas_pos_sos')->nullable();
            $table->integer('semanas_pos_anterior')->nullable();
            $table->integer('semanas_pac_sos')->nullable();
            $table->integer('semanas_pac_anterior')->nullable();
            $table->string('estado', 30)->nullable();
            $table->string('derecho', 100)->nullable();
            $table->string('paga_cuota_moderadora', 5)->nullable();
            $table->string('paga_copago', 200)->nullable();
            $table->string('empleador_tipo_id', 10)->nullable();
            $table->string('empleador_numero_id', 30)->nullable();
            $table->string('empleador_razon_social', 200)->nullable();
            $table->json('convenios')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('cedula');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulta_results');
    }
};
