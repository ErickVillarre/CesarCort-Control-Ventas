<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The API accepts more values than the original MySQL ENUMs, including contado and credito.
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('metodo_pago', 30)->nullable()->change();
            $table->string('tipo_operacion', 30)->change();
            $table->string('referencia_demo', 40)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique(['referencia_demo']);
            $table->dropColumn('referencia_demo');
        });
        // Payment columns stay strings on rollback to avoid truncating existing values.
    }
};
