<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('giro_reparacion_celulares');
        if (! $schema->hasTable('usuarios_empresa')) {
            $schema->create('usuarios_empresa', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('name', 150);
                $table->string('email', 160);
                $table->string('password');
                $table->string('rol_empresa', 40)->default('propietario')->index();
                $table->boolean('debe_cambiar_password')->default(false);
                $table->timestamp('password_temporal_generada_at')->nullable();
                $table->boolean('activo')->default(true);
                $table->rememberToken();
                $table->timestamps();
                $table->unique(['empresa_id', 'email']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('giro_reparacion_celulares')->dropIfExists('usuarios_empresa');
    }
};
