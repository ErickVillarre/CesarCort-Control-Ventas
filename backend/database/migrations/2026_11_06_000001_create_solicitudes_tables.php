<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitante_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->string('asunto', 160);
            $table->text('descripcion');
            $table->string('prioridad', 20)->default('media');
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_solicitud');
            $table->string('decision', 20)->nullable();
            $table->text('resolucion')->nullable();
            $table->foreignId('decidido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decidido_at')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();
            $table->index(['estado', 'prioridad']);
        });

        Schema::create('solicitud_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('evento', 30);
            $table->string('decision', 20)->nullable();
            $table->text('detalle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_eventos');
        Schema::dropIfExists('solicitudes');
    }
};
