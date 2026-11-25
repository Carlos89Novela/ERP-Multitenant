<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('giro_abarrotes');
        if ($schema->hasTable('productos_abarrotes') && ! $schema->hasColumn('productos_abarrotes', 'imagen_path')) {
            $schema->table('productos_abarrotes', function (Blueprint $table) {
                $table->string('imagen_path', 255)->nullable()->after('precio_venta');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('giro_abarrotes');
        if ($schema->hasTable('productos_abarrotes') && $schema->hasColumn('productos_abarrotes', 'imagen_path')) {
            $schema->table('productos_abarrotes', function (Blueprint $table) {
                $table->dropColumn('imagen_path');
            });
        }
    }
};
