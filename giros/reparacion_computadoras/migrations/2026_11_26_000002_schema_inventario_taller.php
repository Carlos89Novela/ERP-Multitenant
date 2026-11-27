<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function conn(): string
    {
        $dbCfg = config('database.connections.mysql', []);
        $dbCfg['database'] = \Giros\ReparacionComputadoras\Services\ContextoPropietarioTaller::resolverNombreBaseDatos();
        config(['database.connections.taller_rep_db' => $dbCfg]);
        \Illuminate\Support\Facades\DB::purge('taller_rep_db');

        return 'taller_rep_db';
    }

    public function up(): void
    {
        $schema = Schema::connection($this->conn());

        if (! $schema->hasTable('sucursales_taller')) {
            $schema->create('sucursales_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->string('codigo', 40)->unique();
                $table->string('nombre', 150);
                $table->boolean('es_matriz')->default(false)->index();
                $table->boolean('activa')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('almacenes_taller')) {
            $schema->create('almacenes_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->foreignId('sucursal_id')
                    ->nullable()
                    ->constrained('sucursales_taller')
                    ->nullOnDelete();
                $table->string('codigo', 40)->unique();
                $table->string('nombre', 150);
                $table->string('tipo', 30)->default('principal')->index();
                $table->boolean('es_virtual')->default(false)->index();
                $table->boolean('permite_existencias')->default(true);
                $table->boolean('activo')->default(true)->index();
                $table->timestamps();

                $table->index(['sucursal_id', 'tipo'], 'alm_taller_suc_tipo_idx');
            });
        }

        if (! $schema->hasTable('categorias_refaccion_taller')) {
            $schema->create('categorias_refaccion_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->string('nombre', 140);
                $table->text('descripcion')->nullable();
                $table->boolean('activa')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('productos_taller')) {
            $schema->create('productos_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->foreignId('categoria_id')
                    ->nullable()
                    ->constrained('categorias_refaccion_taller')
                    ->nullOnDelete();
                $table->string('sku', 80)->unique();
                $table->string('nombre', 180);
                $table->string('unidad_medida', 40)->default('pieza');
                $table->decimal('costo_referencia', 18, 4)->default(0);
                $table->decimal('precio_venta', 18, 4)->default(0);
                $table->boolean('activo')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('existencias_almacen_taller')) {
            $schema->create('existencias_almacen_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->foreignId('almacen_id')
                    ->constrained('almacenes_taller')
                    ->cascadeOnDelete();
                $table->foreignId('producto_id')
                    ->constrained('productos_taller')
                    ->cascadeOnDelete();
                $table->decimal('cantidad', 18, 4)->default(0);
                $table->decimal('cantidad_reservada', 18, 4)->default(0);
                $table->decimal('costo_promedio', 18, 4)->default(0);
                $table->timestamps();

                $table->unique(['almacen_id', 'producto_id'], 'ext_alm_taller_alm_prod_unique');
                $table->index(['producto_id'], 'ext_alm_taller_prod_idx');
            });
        }

        if (! $schema->hasTable('movimientos_inventario_taller')) {
            $schema->create('movimientos_inventario_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->foreignId('almacen_id')
                    ->constrained('almacenes_taller')
                    ->restrictOnDelete();
                $table->foreignId('producto_id')
                    ->constrained('productos_taller')
                    ->restrictOnDelete();
                $table->uuid('grupo_id')->index();
                $table->string('folio', 60)->unique();
                $table->string('tipo', 40)->index();
                $table->decimal('cantidad', 18, 4);
                $table->decimal('existencia_anterior', 18, 4);
                $table->decimal('existencia_nueva', 18, 4);
                $table->decimal('costo_unitario', 18, 4)->nullable();
                $table->string('referencia_tipo', 150)->nullable();
                $table->unsignedBigInteger('referencia_id')->nullable();
                $table->text('motivo');
                $table->unsignedBigInteger('usuario_id')->nullable()->index();
                $table->timestamp('ocurrido_at')->index();
                $table->timestamp('created_at')->nullable();

                $table->index(['almacen_id', 'producto_id', 'ocurrido_at'], 'mov_inv_taller_kardex_idx');
                $table->index(['grupo_id'], 'mov_inv_taller_grupo_idx');
            });
        }

        if (! $schema->hasTable('recepciones_inventario_taller')) {
            $schema->create('recepciones_inventario_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->foreignId('almacen_id')
                    ->constrained('almacenes_taller')
                    ->restrictOnDelete();
                $table->uuid('grupo_id')->unique();
                $table->string('folio', 60)->unique();
                $table->string('tipo', 40)->index();
                $table->string('referencia_externa', 150)->nullable();
                $table->text('motivo');
                $table->unsignedInteger('total_lineas')->default(0);
                $table->decimal('importe_total', 18, 4)->default(0);
                $table->unsignedBigInteger('recibido_por_id')->nullable()->index();
                $table->timestamp('recibido_at')->index();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('recepcion_inventario_taller_detalles')) {
            $schema->create('recepcion_inventario_taller_detalles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('recepcion_inventario_taller_id')
                    ->constrained('recepciones_inventario_taller', indexName: 'rec_det_taller_header_fk')
                    ->cascadeOnDelete();
                $table->foreignId('producto_id')
                    ->constrained('productos_taller', indexName: 'rec_det_taller_prod_fk')
                    ->restrictOnDelete();
                $table->decimal('cantidad', 18, 4);
                $table->decimal('costo_unitario', 18, 4);
                $table->decimal('subtotal', 18, 4);
                $table->timestamps();

                $table->unique(['recepcion_inventario_taller_id', 'producto_id'], 'rec_det_taller_unique');
            });
        }

        if (! $schema->hasTable('reservas_inventario_taller')) {
            $schema->create('reservas_inventario_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->foreignId('almacen_id')
                    ->constrained('almacenes_taller')
                    ->restrictOnDelete();
                $table->foreignId('producto_id')
                    ->constrained('productos_taller')
                    ->restrictOnDelete();
                $table->unsignedBigInteger('orden_reparacion_id')->nullable()->index();
                $table->string('orden_folio', 50)->nullable()->index();
                $table->decimal('cantidad_reservada', 18, 4);
                $table->decimal('cantidad_consumida', 18, 4)->default(0);
                $table->decimal('cantidad_liberada', 18, 4)->default(0);
                $table->string('estado', 30)->default('activa')->index();
                $table->text('motivo');
                $table->unsignedBigInteger('reservado_por_id')->nullable()->index();
                $table->unsignedBigInteger('actualizado_por_id')->nullable()->index();
                $table->timestamp('reservado_at')->nullable();
                $table->timestamp('finalizado_at')->nullable();
                $table->timestamps();

                $table->index(['almacen_id', 'estado'], 'res_inv_taller_alm_est_idx');
            });
        }

        if (! $schema->hasTable('transferencias_inventario_taller')) {
            $schema->create('transferencias_inventario_taller', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->default(1)->index();
                $table->foreignId('almacen_origen_id')
                    ->constrained('almacenes_taller', indexName: 'trf_taller_orig_fk')
                    ->restrictOnDelete();
                $table->foreignId('almacen_destino_id')
                    ->constrained('almacenes_taller', indexName: 'trf_taller_dest_fk')
                    ->restrictOnDelete();
                $table->foreignId('almacen_transito_id')
                    ->nullable()
                    ->constrained('almacenes_taller', indexName: 'trf_taller_trns_fk')
                    ->nullOnDelete();
                $table->uuid('grupo_id')->index();
                $table->string('folio', 50)->unique();
                $table->string('estado', 30)->default('solicitada')->index();
                $table->string('motivo', 1000);
                $table->text('observaciones_despacho')->nullable();
                $table->text('observaciones_recepcion')->nullable();
                $table->unsignedInteger('total_lineas')->default(0);
                $table->decimal('costo_total', 18, 4)->default(0);
                $table->unsignedBigInteger('solicitado_por_id')->nullable()->index();
                $table->unsignedBigInteger('despachado_por_id')->nullable()->index();
                $table->unsignedBigInteger('recibido_por_id')->nullable()->index();
                $table->timestamp('solicitado_at')->nullable()->index();
                $table->timestamp('despachado_at')->nullable()->index();
                $table->timestamp('recibido_at')->nullable()->index();
                $table->timestamps();

                $table->index(['almacen_origen_id'], 'trf_taller_orig_idx');
                $table->index(['almacen_destino_id'], 'trf_taller_dest_idx');
            });
        }

        if (! $schema->hasTable('transferencia_inventario_taller_detalles')) {
            $schema->create('transferencia_inventario_taller_detalles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('transferencia_inventario_taller_id')
                    ->constrained('transferencias_inventario_taller', indexName: 'trf_det_taller_hdr_fk')
                    ->cascadeOnDelete();
                $table->foreignId('producto_id')
                    ->constrained('productos_taller', indexName: 'trf_det_taller_prd_fk')
                    ->restrictOnDelete();
                $table->decimal('cantidad', 18, 4)->default(0);
                $table->decimal('cantidad_solicitada', 18, 4)->default(0);
                $table->decimal('cantidad_despachada', 18, 4)->default(0);
                $table->decimal('cantidad_recibida', 18, 4)->default(0);
                $table->decimal('costo_unitario', 18, 4)->default(0);
                $table->decimal('subtotal', 18, 4)->default(0);
                $table->timestamps();

                $table->index(['transferencia_inventario_taller_id', 'producto_id'], 'trf_det_taller_prd_idx');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->conn());
        $schema->dropIfExists('transferencia_inventario_taller_detalles');
        $schema->dropIfExists('transferencias_inventario_taller');
        $schema->dropIfExists('reservas_inventario_taller');
        $schema->dropIfExists('recepcion_inventario_taller_detalles');
        $schema->dropIfExists('recepciones_inventario_taller');
        $schema->dropIfExists('movimientos_inventario_taller');
        $schema->dropIfExists('existencias_almacen_taller');
        $schema->dropIfExists('productos_taller');
        $schema->dropIfExists('categorias_refaccion_taller');
        $schema->dropIfExists('almacenes_taller');
        $schema->dropIfExists('sucursales_taller');
    }
};