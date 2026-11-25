<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('giro_abarrotes');

        if (! $schema->hasTable('productos_abarrotes')) {
            $schema->create('productos_abarrotes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('codigo_barras', 80)->nullable();
                $table->string('nombre', 160);
                $table->string('unidad_medida', 30)->default('pieza'); // pieza | kg | litro | caja
                $table->decimal('costo_compra', 12, 2)->default(0);
                $table->decimal('precio_venta', 12, 2)->default(0);
                $table->decimal('existencia', 12, 3)->default(0);
                $table->date('fecha_caducidad_proxima')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('promociones_descuentos_abarrotes')) {
            $schema->create('promociones_descuentos_abarrotes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('nombre', 140);
                $table->string('proveedor_campana', 120)->nullable();
                $table->string('tipo_promocion', 40)->default('porcentaje'); // porcentaje | 2x1 | precio_especial | volumen
                $table->decimal('valor_descuento', 12, 2)->default(0);
                $table->boolean('activa')->default(true);
                $table->date('vigencia_inicio')->nullable();
                $table->date('vigencia_fin')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('giro_abarrotes');
        $schema->dropIfExists('promociones_descuentos_abarrotes');
        $schema->dropIfExists('productos_abarrotes');
    }
};
