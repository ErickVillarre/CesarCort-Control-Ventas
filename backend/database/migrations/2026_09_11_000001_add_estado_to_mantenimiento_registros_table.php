<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimiento_registros', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimiento_registros', 'estado')) {
                $table->enum('estado', ['pendiente', 'en_proceso', 'terminado', 'cancelado'])
                    ->default('terminado')
                    ->after('tipo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mantenimiento_registros', function (Blueprint $table) {
            if (Schema::hasColumn('mantenimiento_registros', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};
