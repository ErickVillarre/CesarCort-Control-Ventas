<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->enum('tipo', ['credito', 'cuenta']);
            $table->decimal('limite', 10, 2)->default(0);
            $table->decimal('saldo_actual', 10, 2)->default(0);
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['activo', 'cerrado', 'vencido'])->default('activo');
            $table->text('observacion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creditos');
    }
};