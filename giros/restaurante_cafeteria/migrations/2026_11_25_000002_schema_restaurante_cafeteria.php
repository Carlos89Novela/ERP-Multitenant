<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('giro_restaurante_cafeteria');

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

        if (! $schema->hasTable('mesas_restaurante')) {
            $schema->create('mesas_restaurante', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('nombre_numero', 60);
                $table->unsignedSmallInteger('capacidad')->default(4);
                $table->string('estado', 25)->default('disponible');
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('recetas_platillos')) {
            $schema->create('recetas_platillos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('nombre', 140);
                $table->string('categoria', 80)->nullable();
                $table->text('ingredientes_instrucciones')->nullable();
                $table->decimal('costo_insumos', 12, 2)->default(0);
                $table->decimal('precio_venta', 12, 2)->default(0);
                $table->string('imagen_path', 255)->nullable();
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('comandas_restaurante')) {
            $schema->create('comandas_restaurante', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->unsignedBigInteger('mesa_id')->nullable()->index();
                $table->string('folio', 50);
                $table->string('estado', 30)->default('abierta');
                $table->decimal('total', 12, 2)->default(0);
                $table->text('notas')->nullable();
                $table->timestamps();
                $table->unique(['empresa_id', 'folio']);
            });
        }

        if (! $schema->hasTable('promociones_combos_restaurante')) {
            $schema->create('promociones_combos_restaurante', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('nombre', 140);
                $table->text('descripcion')->nullable();
                $table->decimal('precio_combo', 12, 2)->default(0);
                $table->decimal('descuento_porcentaje', 5, 2)->default(0);
                $table->boolean('activa')->default(true);
                $table->date('vigencia_inicio')->nullable();
                $table->date('vigencia_fin')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('giro_restaurante_cafeteria');
        $schema->dropIfExists('promociones_combos_restaurante');
        $schema->dropIfExists('comandas_restaurante');
        $schema->dropIfExists('recetas_platillos');
        $schema->dropIfExists('mesas_restaurante');
        $schema->dropIfExists('usuarios_empresa');
    }
};
