<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('cliente_id');
            $table->decimal('igv', 10, 2)->default(0)->after('subtotal');
            $table->decimal('monto_recibido', 10, 2)->nullable()->after('tipo_operacion');
            $table->decimal('vuelto', 10, 2)->default(0)->after('monto_recibido');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'igv', 'monto_recibido', 'vuelto']);
        });
    }
};