<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('giro_reparacion_computadoras');

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

        if (! $schema->hasTable('equipos_taller')) {
            $schema->create('equipos_taller', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('tipo_equipo', 80);
                $table->string('marca', 80)->nullable();
                $table->string('modelo', 100)->nullable();
                $table->string('numero_serie', 120)->nullable();
                $table->text('accesorios_recibidos')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('ordenes_reparacion')) {
            $schema->create('ordenes_reparacion', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->unsignedBigInteger('equipo_id')->index();
                $table->string('folio', 50);
                $table->string('estado', 30)->default('recibido');
                $table->text('falla_reportada')->nullable();
                $table->text('diagnostico_tecnico')->nullable();
                $table->decimal('costo_estimado', 12, 2)->default(0);
                $table->timestamps();
                $table->unique(['empresa_id', 'folio']);
            });
        }

        if (! $schema->hasTable('transferencias_taller')) {
            $schema->create('transferencias_taller', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('folio', 50);
                $table->string('origen_sucursal', 120);
                $table->string('destino_sucursal', 120);
                $table->string('concepto', 180);
                $table->decimal('cantidad', 12, 3)->default(1);
                $table->string('estado', 30)->default('pendiente');
                $table->text('notas')->nullable();
                $table->timestamps();
                $table->unique(['empresa_id', 'folio']);
            });
        }

        if (! $schema->hasTable('promociones_combos_taller')) {
            $schema->create('promociones_combos_taller', function (Blueprint $table) {
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
        $schema = Schema::connection('giro_reparacion_computadoras');
        $schema->dropIfExists('promociones_combos_taller');
        $schema->dropIfExists('transferencias_taller');
        $schema->dropIfExists('ordenes_reparacion');
        $schema->dropIfExists('equipos_taller');
        $schema->dropIfExists('usuarios_empresa');
    }
};
