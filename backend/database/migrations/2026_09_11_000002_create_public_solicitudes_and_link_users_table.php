<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'cliente_id')) {
                $table->foreignId('cliente_id')->nullable()->after('employee_id')->constrained('clientes')->nullOnDelete();
            }
        });

        if (!Schema::hasTable('public_solicitudes')) {
            Schema::create('public_solicitudes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
                $table->string('nombre');
                $table->string('email')->nullable();
                $table->string('telefono');
                $table->string('dni_ruc')->nullable();
                $table->string('direccion')->nullable();
                $table->string('producto')->nullable();
                $table->unsignedInteger('cantidad_aproximada')->default(1);
                $table->text('mensaje')->nullable();
                $table->enum('estado', ['pendiente', 'en_revision', 'atendida', 'descartada'])->default('pendiente');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('public_solicitudes');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cliente_id')) {
                $table->dropConstrainedForeignId('cliente_id');
            }
        });
    }
};
