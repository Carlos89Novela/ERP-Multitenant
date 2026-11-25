<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresas')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 40)->default('empleado')->after('password');
            }
            if (! Schema::hasColumn('users', 'es_propietario')) {
                $table->boolean('es_propietario')->default(false)->after('role');
            }
            if (! Schema::hasColumn('users', 'puede_autogestionar_template')) {
                $table->boolean('puede_autogestionar_template')->default(false)->after('es_propietario');
            }
            if (! Schema::hasColumn('users', 'es_operador_plataforma')) {
                $table->boolean('es_operador_plataforma')->default(false)->after('puede_autogestionar_template');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach (['es_operador_plataforma', 'puede_autogestionar_template', 'es_propietario', 'role'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('users', 'empresa_id')) {
                $table->dropConstrainedForeignId('empresa_id');
            }
        });
    }
};
