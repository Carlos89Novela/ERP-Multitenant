<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('giro_reparacion_celulares');

        if (! $schema->hasTable('equipos_celulares')) {
            $schema->create('equipos_celulares', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('marca', 80);
                $table->string('modelo', 100);
                $table->string('imei', 30)->nullable();
                $table->string('patron_pin_nota', 120)->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('ordenes_reparacion_celulares')) {
            $schema->create('ordenes_reparacion_celulares', function (Blueprint $table) {
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

        if (! $schema->hasTable('promociones_combos_celulares')) {
            $schema->create('promociones_combos_celulares', function (Blueprint $table) {
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
        $schema = Schema::connection('giro_reparacion_celulares');
        $schema->dropIfExists('promociones_combos_celulares');
        $schema->dropIfExists('ordenes_reparacion_celulares');
        $schema->dropIfExists('equipos_celulares');
    }
};
