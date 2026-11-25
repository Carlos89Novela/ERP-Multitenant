<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('template_configuraciones')) {
            Schema::table('template_configuraciones', function (Blueprint $table) {
                if (! Schema::hasColumn('template_configuraciones', 'ambito')) {
                    $table->string('ambito', 30)->default('empresa')->after('id')->index();
                }
                if (Schema::hasColumn('template_configuraciones', 'empresa_id')) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('template_configuraciones') && Schema::hasColumn('template_configuraciones', 'ambito')) {
            Schema::table('template_configuraciones', function (Blueprint $table) {
                $table->dropColumn('ambito');
            });
        }
    }
};
