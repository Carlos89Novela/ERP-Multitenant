<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bolsa_timbres_lotes')) {
            Schema::table('bolsa_timbres_lotes', function (Blueprint $table) {
                if (! Schema::hasColumn('bolsa_timbres_lotes', 'xml_path')) {
                    $table->string('xml_path', 255)->nullable()->after('costo_unitario');
                }
                if (! Schema::hasColumn('bolsa_timbres_lotes', 'pdf_path')) {
                    $table->string('pdf_path', 255)->nullable()->after('xml_path');
                }
            });
        }

        if (Schema::hasTable('empresas')) {
            Schema::table('empresas', function (Blueprint $table) {
                if (! Schema::hasColumn('empresas', 'plazo_meses')) {
                    $table->unsignedTinyInteger('plazo_meses')->default(1)->after('giro_bloqueado'); // 1, 3, 6, 9, 12
                }
                if (! Schema::hasColumn('empresas', 'monto_renta')) {
                    $table->decimal('monto_renta', 12, 2)->default(0)->after('plazo_meses');
                }
                if (! Schema::hasColumn('empresas', 'fecha_vencimiento_licencia')) {
                    $table->date('fecha_vencimiento_licencia')->nullable()->after('monto_renta');
                }
                if (! Schema::hasColumn('empresas', 'dias_gracia_licencia')) {
                    $table->unsignedSmallInteger('dias_gracia_licencia')->default(30)->after('fecha_vencimiento_licencia');
                }
                if (! Schema::hasColumn('empresas', 'estado_licencia')) {
                    $table->string('estado_licencia', 30)->default('activa')->after('dias_gracia_licencia'); // activa | gracia | suspendida_morosidad
                }
            });
        }

        if (! Schema::hasTable('facturas_clientes')) {
            Schema::create('facturas_clientes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->string('folio', 60)->unique();
                $table->string('concepto_tipo', 30); // renta_plataforma | paquete_timbres | mixto
                $table->unsignedTinyInteger('plazo_meses')->nullable(); // 1, 3, 6, 9, 12 cuando aplica
                $table->unsignedInteger('timbres_incluidos')->default(0);
                $table->decimal('subtotal', 12, 2);
                $table->decimal('iva', 12, 2)->default(0);
                $table->decimal('total', 12, 2);
                $table->string('estatus', 25)->default('pendiente'); // pendiente | pagada | vencida | cancelada
                $table->date('fecha_emision');
                $table->date('fecha_vencimiento')->nullable();
                $table->timestamp('pagada_at')->nullable();
                $table->string('xml_path', 255)->nullable();
                $table->string('pdf_path', 255)->nullable();
                $table->text('notas')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas_clientes');

        if (Schema::hasTable('empresas')) {
            Schema::table('empresas', function (Blueprint $table) {
                foreach (['estado_licencia', 'dias_gracia_licencia', 'fecha_vencimiento_licencia', 'monto_renta', 'plazo_meses'] as $col) {
                    if (Schema::hasColumn('empresas', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('bolsa_timbres_lotes')) {
            Schema::table('bolsa_timbres_lotes', function (Blueprint $table) {
                foreach (['pdf_path', 'xml_path'] as $col) {
                    if (Schema::hasColumn('bolsa_timbres_lotes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
