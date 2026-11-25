<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->string('giro_comercial', 60)->nullable();
            $table->string('rol', 40)->nullable();
            $table->string('fuente_primaria', 100)->default('Inter, system-ui, sans-serif');
            $table->string('fuente_secundaria', 100)->default('JetBrains Mono, monospace');
            $table->unsignedTinyInteger('tamano_base_px')->default(14);
            $table->string('color_primario', 30)->default('#6366f1');
            $table->string('color_secundario', 30)->default('#3b82f6');
            $table->string('color_acento', 30)->default('#10b981');
            $table->string('color_fondo_pagina', 30)->default('#09090b');
            $table->string('color_fondo_superficie', 30)->default('#18181b');
            $table->string('fondo_tarjeta', 120)->default('#111115');
            $table->string('color_texto_principal', 30)->default('#f4f4f5');
            $table->string('color_texto_secundario', 30)->default('#a1a1aa');
            $table->string('color_borde', 30)->default('#27272a');
            $table->string('tipo_fondo', 20)->default('solido');
            $table->string('valor_fondo', 500)->nullable();
            $table->string('estilo_icono', 30)->default('heroicons');
            $table->string('radio_bordes', 25)->default('0.75rem');
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('actualizado_por_id')->nullable();
            $table->timestamps();
        });

        Schema::create('pagina_bloques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('giro_comercial', 60)->nullable();
            $table->string('contexto_ruta', 120);
            $table->string('clave_bloque', 80);
            $table->string('titulo', 140);
            $table->string('subtitulo', 255)->nullable();
            $table->string('icono', 80)->nullable();
            $table->string('ruta_destino', 140)->nullable();
            $table->string('texto_accion', 80)->nullable();
            $table->unsignedSmallInteger('orden')->default(10);
            $table->boolean('visible')->default(true);
            $table->json('roles_permitidos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagina_bloques');
        Schema::dropIfExists('template_configuraciones');
    }
};
