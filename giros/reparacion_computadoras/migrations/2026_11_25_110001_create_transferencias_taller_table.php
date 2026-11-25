<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('giro_reparacion_computadoras');

        if (! $schema->hasTable('transferencias_taller')) {
            $schema->create('transferencias_taller', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('folio', 50);
                $table->string('origen_sucursal', 120);
                $table->string('destino_sucursal', 120);
                $table->string('concepto', 180);
                $table->decimal('cantidad', 12, 3)->default(1);
                $table->string('estado', 30)->default('pendiente'); // pendiente | en_transito | recibida | cancelada
                $table->text('notas')->nullable();
                $table->timestamps();
                $table->unique(['empresa_id', 'folio']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('giro_reparacion_computadoras')->dropIfExists('transferencias_taller');
    }
};
