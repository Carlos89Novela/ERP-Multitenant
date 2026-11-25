<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bolsa_timbres_lotes', function (Blueprint $table) {
            $table->id();
            $table->string('folio_compra', 80)->unique();
            $table->string('proveedor', 140)->nullable();
            $table->unsignedInteger('cantidad_comprada');
            $table->unsignedInteger('cantidad_disponible');
            $table->decimal('costo_unitario', 10, 4)->nullable();
            $table->timestamp('fecha_compra')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('asignaciones_timbres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('bolsa_timbres_lotes')->nullOnDelete();
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('saldo_empresa_posterior');
            $table->string('motivo', 180)->nullable();
            $table->unsignedBigInteger('asignado_por_id')->nullable();
            $table->timestamps();
        });

        // Telemetria tecnica y de servicio por empresa: sin acceso a datos operativos del cliente
        Schema::create('monitoreo_servicios_empresa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();
            $table->string('estado_pagina', 24)->default('activo'); // activo | degradado | inactivo
            $table->string('estado_timbres', 24)->default('ok'); // ok | bajo | agotado
            $table->unsignedInteger('timbres_reportados')->default(0);
            $table->unsignedInteger('latencia_ms')->nullable();
            $table->unsignedSmallInteger('paginas_activas_count')->default(0);
            $table->timestamp('ultimo_latido_at')->nullable();
            $table->string('ultimo_evento_servicio', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoreo_servicios_empresa');
        Schema::dropIfExists('asignaciones_timbres');
        Schema::dropIfExists('bolsa_timbres_lotes');
    }
};
