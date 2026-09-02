<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'codigo')) {
                $table->string('codigo')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('productos', 'codigo_barras')) {
                $table->string('codigo_barras')->nullable()->unique()->after('codigo');
            }
            if (!Schema::hasColumn('productos', 'costo')) {
                $table->decimal('costo', 10, 2)->nullable()->after('precio');
            }
        });

        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'codigo_cliente')) {
                $table->string('codigo_cliente')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('clientes', 'ruc')) {
                $table->string('ruc', 11)->nullable()->unique()->after('dni');
            }
            if (!Schema::hasColumn('clientes', 'razon_social')) {
                $table->string('razon_social')->nullable()->after('ruc');
            }
        });

        Schema::table('empleados', function (Blueprint $table) {
            $columns = [
                'codigo' => fn () => $table->string('codigo')->nullable()->unique()->after('id'),
                'foto' => fn () => $table->string('foto')->nullable()->after('codigo'),
                'apellidos' => fn () => $table->string('apellidos')->nullable()->after('nombre'),
                'dni' => fn () => $table->string('dni', 20)->nullable()->unique()->after('apellidos'),
                'fecha_nacimiento' => fn () => $table->date('fecha_nacimiento')->nullable()->after('dni'),
                'direccion' => fn () => $table->string('direccion')->nullable()->after('telefono'),
                'estado_civil' => fn () => $table->string('estado_civil')->nullable()->after('direccion'),
                'contacto_emergencia' => fn () => $table->string('contacto_emergencia')->nullable()->after('estado_civil'),
                'parentesco_emergencia' => fn () => $table->string('parentesco_emergencia')->nullable()->after('contacto_emergencia'),
                'telefono_emergencia' => fn () => $table->string('telefono_emergencia')->nullable()->after('parentesco_emergencia'),
                'area' => fn () => $table->string('area')->nullable()->after('cargo'),
                'fecha_ingreso' => fn () => $table->date('fecha_ingreso')->nullable()->after('area'),
                'tipo_contrato' => fn () => $table->string('tipo_contrato')->nullable()->after('fecha_ingreso'),
                'horario' => fn () => $table->string('horario')->nullable()->after('tipo_contrato'),
                'sueldo' => fn () => $table->decimal('sueldo', 10, 2)->nullable()->after('horario'),
                'fecha_pago' => fn () => $table->unsignedTinyInteger('fecha_pago')->nullable()->after('sueldo'),
                'banco' => fn () => $table->string('banco')->nullable()->after('fecha_pago'),
                'cuenta_bancaria' => fn () => $table->string('cuenta_bancaria')->nullable()->after('banco'),
                'regimen_pensionario' => fn () => $table->string('regimen_pensionario')->nullable()->after('cuenta_bancaria'),
                'cuspp' => fn () => $table->string('cuspp')->nullable()->after('regimen_pensionario'),
            ];

            foreach ($columns as $column => $definition) {
                if (!Schema::hasColumn('empleados', $column)) {
                    $definition();
                }
            }
        });

        Schema::create('caja_aperturas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('caja');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('abierta_at');
            $table->dateTime('cerrada_at')->nullable();
            $table->decimal('fondo_inicial', 10, 2)->default(0);
            $table->decimal('monto_contado', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->text('observacion')->nullable();
            $table->text('justificacion_diferencia')->nullable();
            $table->enum('estado', ['abierta', 'pendiente_revision', 'cerrada'])->default('abierta');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('ventas', 'vendedor_id')) {
                $table->foreignId('vendedor_id')->nullable()->after('cliente_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('ventas', 'caja_apertura_id')) {
                $table->foreignId('caja_apertura_id')->nullable()->after('vendedor_id')->constrained('caja_aperturas')->nullOnDelete();
            }
            if (!Schema::hasColumn('ventas', 'estado')) {
                $table->enum('estado', ['preventa', 'pendiente_caja', 'cobrada', 'anulada', 'cancelada'])->default('cobrada')->after('total');
            }
            if (!Schema::hasColumn('ventas', 'comprobante_tipo')) {
                $table->enum('comprobante_tipo', ['boleta', 'factura', 'interno'])->default('interno')->after('estado');
            }
            if (!Schema::hasColumn('ventas', 'descuento')) {
                $table->decimal('descuento', 10, 2)->default(0)->after('igv');
            }
        });

        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('caja_apertura_id')->nullable()->constrained('caja_aperturas')->nullOnDelete();
            $table->enum('metodo', ['efectivo', 'yape', 'transferencia', 'tarjeta', 'credito', 'dinero_cuenta']);
            $table->decimal('monto', 10, 2);
            $table->string('banco')->nullable();
            $table->string('numero_operacion')->nullable();
            $table->string('evidencia')->nullable();
            $table->enum('estado', ['pendiente_validacion', 'validado', 'rechazado'])->default('validado');
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_apertura_id')->constrained('caja_aperturas')->cascadeOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->enum('tipo', ['ingreso', 'egreso', 'retiro', 'reposicion']);
            $table->enum('metodo', ['efectivo', 'yape', 'transferencia', 'tarjeta', 'otro'])->default('efectivo');
            $table->decimal('monto', 10, 2);
            $table->string('categoria')->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('caja_chica_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_apertura_id')->nullable()->constrained('caja_aperturas')->nullOnDelete();
            $table->enum('tipo', ['fondo', 'reposicion', 'gasto']);
            $table->decimal('monto', 10, 2);
            $table->string('categoria')->nullable();
            $table->string('comprobante')->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dinero_cuenta_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->enum('tipo', ['deposito', 'consumo', 'ajuste']);
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_resultante', 10, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('crm_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['llamada', 'whatsapp', 'visita', 'cotizacion', 'postventa']);
            $table->enum('estado', ['pendiente', 'realizado', 'vencido'])->default('pendiente');
            $table->dateTime('programado_at')->nullable();
            $table->dateTime('realizado_at')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['agotado', 'stock_bajo', 'solicitado_cliente']);
            $table->integer('cantidad_aproximada')->default(1);
            $table->text('observacion')->nullable();
            $table->enum('estado', ['pendiente', 'en_revision', 'atendido', 'descartado'])->default('pendiente');
            $table->timestamps();
        });

        Schema::create('autorizacion_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('precio_normal', 10, 2);
            $table->decimal('precio_solicitado', 10, 2);
            $table->decimal('diferencia', 10, 2);
            $table->text('motivo');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'vencida'])->default('pendiente');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('aprobado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mantenimiento_maquinarias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('tipo')->default('Maquinaria');
            $table->string('marca')->default('Por registrar');
            $table->string('modelo')->default('Por registrar');
            $table->string('numero_serie')->default('Por registrar');
            $table->string('fotografia')->nullable();
            $table->date('fecha_compra')->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->string('ubicacion')->default('Taller');
            $table->foreignId('responsable_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->enum('estado', ['operativa', 'detenida', 'mantenimiento'])->default('operativa');
            $table->enum('criticidad', ['baja', 'media', 'alta', 'critica'])->default('alta');
            $table->decimal('horas_acumuladas', 10, 2)->default(0);
            $table->string('frecuencia_mantenimiento')->default('Mensual');
            $table->date('ultimo_mantenimiento')->nullable();
            $table->date('proximo_mantenimiento')->nullable();
            $table->decimal('tiempo_promedio_mantenimiento', 10, 2)->default(0);
            $table->decimal('tiempo_inactividad_total', 10, 2)->default(0);
            $table->decimal('costo_acumulado', 10, 2)->default(0);
            $table->string('garantia')->default('Por registrar');
            $table->json('manuales')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('mantenimiento_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquinaria_id')->constrained('mantenimiento_maquinarias')->cascadeOnDelete();
            $table->enum('tipo', ['preventivo', 'correctivo']);
            $table->dateTime('inicio_at');
            $table->dateTime('fin_at')->nullable();
            $table->decimal('duracion_horas', 10, 2)->default(0);
            $table->foreignId('tecnico_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->text('problema_encontrado')->nullable();
            $table->text('actividad_realizada')->nullable();
            $table->text('piezas_retiradas')->nullable();
            $table->text('piezas_instaladas')->nullable();
            $table->decimal('costo_mano_obra', 10, 2)->default(0);
            $table->decimal('costo_repuestos', 10, 2)->default(0);
            $table->enum('estado_final', ['operativa', 'observada', 'detenida'])->default('operativa');
            $table->text('recomendacion')->nullable();
            $table->date('proxima_fecha')->nullable();
            $table->json('evidencias')->nullable();
            $table->timestamps();
        });

        Schema::create('mantenimiento_fallas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquinaria_id')->constrained('mantenimiento_maquinarias')->cascadeOnDelete();
            $table->foreignId('reportado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('criticidad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->enum('estado', ['abierta', 'en_revision', 'resuelta', 'cerrada'])->default('abierta');
            $table->dateTime('reportada_at');
            $table->dateTime('resuelta_at')->nullable();
            $table->decimal('tiempo_inactividad_horas', 10, 2)->default(0);
            $table->text('descripcion');
            $table->text('accion_realizada')->nullable();
            $table->timestamps();
        });

        Schema::create('mantenimiento_repuestos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->foreignId('maquinaria_id')->nullable()->constrained('mantenimiento_maquinarias')->nullOnDelete();
            $table->integer('cantidad_disponible')->default(0);
            $table->integer('stock_minimo')->default(1);
            $table->string('unidad_medida')->default('unidad');
            $table->string('proveedor')->nullable();
            $table->decimal('precio', 10, 2)->default(0);
            $table->string('ubicacion')->nullable();
            $table->enum('estado', ['activo', 'agotado', 'descontinuado'])->default('activo');
            $table->date('ultimo_ingreso_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mantenimiento_pedidos_repuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repuesto_id')->nullable()->constrained('mantenimiento_repuestos')->nullOnDelete();
            $table->foreignId('maquinaria_id')->nullable()->constrained('mantenimiento_maquinarias')->nullOnDelete();
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('cantidad')->default(1);
            $table->enum('urgencia', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->text('motivo');
            $table->enum('estado', ['solicitado', 'en_revision', 'aprobado', 'rechazado', 'pedido', 'recibido', 'instalado', 'cancelado'])->default('solicitado');
            $table->dateTime('revisado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mantenimiento_cambios_piezas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquinaria_id')->constrained('mantenimiento_maquinarias')->cascadeOnDelete();
            $table->foreignId('repuesto_id')->nullable()->constrained('mantenimiento_repuestos')->nullOnDelete();
            $table->foreignId('mantenimiento_id')->nullable()->constrained('mantenimiento_registros')->nullOnDelete();
            $table->integer('cantidad')->default(1);
            $table->text('pieza_retirada')->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('tecnico_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->dateTime('instalado_at');
            $table->timestamps();
        });

        Schema::create('mantenimiento_cortes_energia', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_retorno')->nullable();
            $table->decimal('duracion_horas', 10, 2)->default(0);
            $table->string('area_afectada');
            $table->json('maquinarias_afectadas')->nullable();
            $table->string('trabajo_interrumpido')->nullable();
            $table->string('posible_causa')->nullable();
            $table->text('dano_encontrado')->nullable();
            $table->text('accion_realizada')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('empleado_asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('entrada')->nullable();
            $table->time('salida')->nullable();
            $table->enum('estado', ['presente', 'falta', 'tardanza', 'permiso', 'vacaciones'])->default('presente');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('empleado_movimientos_rrhh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->enum('tipo', ['permiso', 'vacacion', 'adelanto', 'prestamo', 'descuento', 'pago', 'bonificacion', 'alimentacion', 'observacion']);
            $table->decimal('monto', 10, 2)->nullable();
            $table->date('fecha');
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado', 'registrado'])->default('registrado');
            $table->text('descripcion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo', 10, 2);
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'vencida', 'pagada'])->default('pendiente');
            $table->timestamps();
        });

        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id();
            $table->string('proveedor');
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo', 10, 2);
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'vencida', 'pagada'])->default('pendiente');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_campanas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('estado', ['planificada', 'activa', 'pausada', 'cerrada'])->default('planificada');
            $table->date('inicio')->nullable();
            $table->date('fin')->nullable();
            $table->decimal('presupuesto', 10, 2)->default(0);
            $table->text('objetivo')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_publicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->nullable()->constrained('marketing_campanas')->nullOnDelete();
            $table->enum('tipo', ['grabacion', 'edicion', 'publicacion', 'evento']);
            $table->string('titulo');
            $table->string('canal')->nullable();
            $table->dateTime('programado_at')->nullable();
            $table->enum('estado', ['pendiente', 'en_proceso', 'publicado', 'cancelado'])->default('pendiente');
            $table->foreignId('responsable_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_redes_sociales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('enlace');
            $table->string('usuario')->nullable();
            $table->enum('estado', ['configurable', 'activo', 'pausado'])->default('configurable');
            $table->foreignId('responsable_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->date('ultima_publicacion')->nullable();
            $table->date('proxima_publicacion')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_redes_sociales');
        Schema::dropIfExists('marketing_publicaciones');
        Schema::dropIfExists('marketing_campanas');
        Schema::dropIfExists('cuentas_por_pagar');
        Schema::dropIfExists('cuentas_por_cobrar');
        Schema::dropIfExists('empleado_movimientos_rrhh');
        Schema::dropIfExists('empleado_asistencias');
        Schema::dropIfExists('mantenimiento_cortes_energia');
        Schema::dropIfExists('mantenimiento_cambios_piezas');
        Schema::dropIfExists('mantenimiento_pedidos_repuestos');
        Schema::dropIfExists('mantenimiento_repuestos');
        Schema::dropIfExists('mantenimiento_fallas');
        Schema::dropIfExists('mantenimiento_registros');
        Schema::dropIfExists('mantenimiento_maquinarias');
        Schema::dropIfExists('autorizacion_precios');
        Schema::dropIfExists('stock_alertas');
        Schema::dropIfExists('crm_seguimientos');
        Schema::dropIfExists('dinero_cuenta_movimientos');
        Schema::dropIfExists('caja_chica_movimientos');
        Schema::dropIfExists('caja_movimientos');
        Schema::dropIfExists('venta_pagos');

        Schema::table('ventas', function (Blueprint $table) {
            foreach (['vendedor_id', 'caja_apertura_id'] as $column) {
                if (Schema::hasColumn('ventas', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
            foreach (['estado', 'comprobante_tipo', 'descuento'] as $column) {
                if (Schema::hasColumn('ventas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('caja_aperturas');

        Schema::table('empleados', function (Blueprint $table) {
            $columns = [
                'codigo', 'foto', 'apellidos', 'dni', 'fecha_nacimiento', 'direccion',
                'estado_civil', 'contacto_emergencia', 'parentesco_emergencia',
                'telefono_emergencia', 'area', 'fecha_ingreso', 'tipo_contrato',
                'horario', 'sueldo', 'fecha_pago', 'banco', 'cuenta_bancaria',
                'regimen_pensionario', 'cuspp',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('empleados', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('clientes', function (Blueprint $table) {
            foreach (['codigo_cliente', 'ruc', 'razon_social'] as $column) {
                if (Schema::hasColumn('clientes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            foreach (['codigo', 'codigo_barras', 'costo'] as $column) {
                if (Schema::hasColumn('productos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
