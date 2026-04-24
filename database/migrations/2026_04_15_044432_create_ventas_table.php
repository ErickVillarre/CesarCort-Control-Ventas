<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            //
            //Relaciones
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            //
            //Datos de venta
            $table->integer('cantidad');
            $table->decimal('precio',10,2);
            $table->decimal('total',10,2);
            //
            //Metodo de pago
            $table->enum('metodo_pago',['efectivo','tarjeta','yape','plin'])->nullable();
            //
            //Tipo de operacion
            $table->enum('tipo_operacion',['efectivo','saldo','credito']);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
