<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Reparacion de computadoras
        Schema::create('equipos_taller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo_equipo', 80);
            $table->string('marca', 80)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('ordenes_reparacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos_taller')->nullOnDelete();
            $table->string('folio', 50);
            $table->string('estado', 40)->default('recibido');
            $table->text('falla_reportada')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'folio']);
        });

        // 2. Restaurante / cafeteria
        Schema::create('mesas_restaurante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 30);
            $table->unsignedTinyInteger('capacidad')->default(4);
            $table->string('estado', 30)->default('libre');
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
        });

        Schema::create('comandas_restaurante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('mesa_id')->nullable()->constrained('mesas_restaurante')->nullOnDelete();
            $table->string('folio', 50);
            $table->string('estado', 30)->default('abierta');
            $table->timestamps();

            $table->unique(['empresa_id', 'folio']);
        });

        // 3. Abarrotes
        Schema::create('productos_abarrotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo_barras', 60)->nullable();
            $table->string('nombre', 160);
            $table->decimal('stock_actual', 12, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('lotes_abarrotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos_abarrotes')->cascadeOnDelete();
            $table->string('codigo_lote', 60);
            $table->date('fecha_caducidad')->nullable();
            $table->decimal('cantidad', 12, 3)->default(0);
            $table->timestamps();
        });

        // 4. Ferreteria
        Schema::create('productos_ferreteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('sku', 60);
            $table->string('nombre', 160);
            $table->string('unidad_venta', 30)->default('pieza');
            $table->decimal('stock_actual', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'sku']);
        });

        // 5. Reparacion de celulares
        Schema::create('dispositivos_celulares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('marca', 80);
            $table->string('modelo', 100);
            $table->string('imei', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('ordenes_celulares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('dispositivo_id')->nullable()->constrained('dispositivos_celulares')->nullOnDelete();
            $table->string('folio', 50);
            $table->string('estado', 40)->default('recibido');
            $table->text('diagnostico')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'folio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_celulares');
        Schema::dropIfExists('dispositivos_celulares');
        Schema::dropIfExists('productos_ferreteria');
        Schema::dropIfExists('lotes_abarrotes');
        Schema::dropIfExists('productos_abarrotes');
        Schema::dropIfExists('comandas_restaurante');
        Schema::dropIfExists('mesas_restaurante');
        Schema::dropIfExists('ordenes_reparacion');
        Schema::dropIfExists('equipos_taller');
    }
};
