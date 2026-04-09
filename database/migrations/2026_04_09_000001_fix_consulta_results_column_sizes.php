<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulta_results', function (Blueprint $table) {
            $table->text('paga_cuota_moderadora')->nullable()->change();
            $table->text('paga_copago')->nullable()->change();
            $table->string('derecho', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('consulta_results', function (Blueprint $table) {
            $table->string('paga_cuota_moderadora', 5)->nullable()->change();
            $table->string('paga_copago', 200)->nullable()->change();
            $table->string('derecho', 100)->nullable()->change();
        });
    }
};
