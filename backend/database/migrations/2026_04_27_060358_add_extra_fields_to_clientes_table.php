<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('apodo')->nullable()->after('nombre');
            $table->string('dni')->nullable()->after('apodo');
            $table->string('telefono')->nullable()->after('dni');
            $table->string('email')->nullable()->after('telefono');
            $table->string('direccion')->nullable()->after('email');
            $table->string('dni_imagen')->nullable()->after('direccion');
            $table->string('firma_imagen')->nullable()->after('dni_imagen');
            $table->enum('tipo_cliente', ['anonimo', 'regular', 'credito', 'cuenta'])
                  ->default('regular')
                  ->after('firma_imagen');
            $table->boolean('activo')->default(true)->after('tipo_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'apodo',
                'dni',
                'telefono',
                'email',
                'direccion',
                'dni_imagen',
                'firma_imagen',
                'tipo_cliente',
                'activo',
            ]);
        });
    }
};