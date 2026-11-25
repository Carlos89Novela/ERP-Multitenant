<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('giro_ferreteria');

        if (! $schema->hasTable('usuarios_empresa')) {
            $schema->create('usuarios_empresa', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('name', 150);
                $table->string('email', 160);
                $table->string('password');
                $table->string('rol_empresa', 40)->default('propietario')->index();
                $table->boolean('activo')->default(true);
                $table->rememberToken();
                $table->timestamps();
                $table->unique(['empresa_id', 'email']);
            });
        }

        if (! $schema->hasTable('productos_ferreteria')) {
            $schema->create('productos_ferreteria', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('sku', 80)->nullable();
                $table->string('nombre', 160);
                $table->string('unidad_venta', 30)->default('pieza');
                $table->decimal('costo_compra', 12, 2)->default(0);
                $table->decimal('precio_venta', 12, 2)->default(0);
                $table->string('imagen_path', 255)->nullable();
                $table->decimal('existencia', 12, 3)->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('promociones_descuentos_ferreteria')) {
            $schema->create('promociones_descuentos_ferreteria', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('nombre', 140);
                $table->string('proveedor_campana', 120)->nullable();
                $table->string('tipo_promocion', 40)->default('porcentaje');
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
        $schema = Schema::connection('giro_ferreteria');
        $schema->dropIfExists('promociones_descuentos_ferreteria');
        $schema->dropIfExists('productos_ferreteria');
        $schema->dropIfExists('usuarios_empresa');
    }
};
