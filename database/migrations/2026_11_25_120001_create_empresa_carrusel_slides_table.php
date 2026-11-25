<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('empresa_carrusel_slides')) {
            Schema::create('empresa_carrusel_slides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('tipo', 30)->default('slide'); // slide | destacado
                $table->string('titulo', 160);
                $table->string('subtitulo', 255)->nullable();
                $table->text('descripcion')->nullable();
                $table->decimal('precio', 12, 2)->nullable();
                $table->string('imagen_path', 255)->nullable();
                $table->string('icono_texto', 40)->nullable();
                $table->unsignedInteger('orden')->default(1);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_carrusel_slides');
    }
};
