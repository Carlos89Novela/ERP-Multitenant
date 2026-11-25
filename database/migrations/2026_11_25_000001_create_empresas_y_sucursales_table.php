<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 160);
            $table->string('slug', 160)->unique();
            $table->string('rfc', 20)->nullable()->index();
            $table->string('giro_comercial', 60)->index();
            $table->boolean('giro_bloqueado')->default(true);
            $table->boolean('activa')->default(true)->index();
            $table->unsignedInteger('saldo_timbres')->default(0);
            $table->unsignedInteger('umbral_alerta_timbres')->default(25);
            $table->json('paginas_habilitadas')->nullable();
            $table->timestamps();
        });

        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 30);
            $table->string('nombre', 150);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('empresas');
    }
};
