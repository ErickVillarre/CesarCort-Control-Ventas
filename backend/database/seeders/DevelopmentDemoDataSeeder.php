<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevelopmentDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command?->warn('Seeder omitido: solo debe ejecutarse en entorno local.');
            return;
        }

        DB::transaction(function () {
            $this->call(RolesAndDemoUsersSeeder::class);
            $this->products();
            $this->clients();
            $this->employees();
            $this->creditsAndAccounts();
            $this->sales();
            $this->cash();
            $this->humanResources();
            $this->maintenance();
            $this->marketing();
        });
    }

    private function products(): void
    {
        $types = ['melamina', 'canto', 'accesorio', 'servicio', 'medelack'];
        $colors = ['Blanco', 'Grafito', 'Roble', 'Nogal', 'Ceniza', 'Arena', 'Humo', 'Nevado', 'Cedro', 'Plomo'];

        for ($i = 1; $i <= 50; $i++) {
            $type = $types[$i % count($types)];
            $color = $colors[$i % count($colors)];

            Producto::updateOrCreate(
                ['codigo' => 'PROD-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'codigo_barras' => '775000' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                    'nombre' => ucfirst($type) . ' ' . $color . ' demo ' . $i,
                    'precio' => 28 + ($i * 3.7),
                    'costo' => 16 + ($i * 2.1),
                    'stock' => $i % 9 === 0 ? 0 : 12 + ($i % 30),
                    'tipo' => $type,
                    'espesor' => $type === 'melamina' ? ($i % 2 ? '18mm' : '15mm') : null,
                    'canto_tipo' => $type === 'canto' ? ($i % 2 ? 'grueso' : 'delgado') : null,
                    'canto_ancho' => $type === 'canto' ? ($i % 2 ? 'ancho' : 'normal') : null,
                    'color' => in_array($type, ['melamina', 'medelack'], true) ? $color : null,
                ]
            );
        }
    }

    private function clients(): void
    {
        Cliente::firstOrCreate(
            ['tipo_cliente' => 'anonimo'],
            ['codigo_cliente' => 'CLI-000', 'nombre' => 'Cliente Anonimo', 'credito' => 0, 'saldo' => 0, 'activo' => true]
        );

        for ($i = 1; $i <= 40; $i++) {
            Cliente::updateOrCreate(
                ['codigo_cliente' => 'CLI-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'nombre' => 'Cliente Demo ' . $i,
                    'apodo' => 'Cliente ' . $i,
                    'dni' => str_pad((string) (70000000 + $i), 8, '0', STR_PAD_LEFT),
                    'ruc' => $i % 5 === 0 ? '20' . str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT) : null,
                    'razon_social' => $i % 5 === 0 ? 'Empresa Demo ' . $i : null,
                    'telefono' => '91' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                    'email' => 'cliente' . $i . '@example.com',
                    'direccion' => 'Direccion configurable ' . $i,
                    'credito' => $i % 3 === 0 ? 1500 : 0,
                    'saldo' => $i % 4 === 0 ? 300 : 0,
                    'tipo_cliente' => $i % 3 === 0 ? 'credito' : ($i % 4 === 0 ? 'cuenta' : 'regular'),
                    'activo' => true,
                ]
            );
        }
    }

    private function employees(): void
    {
        $roles = ['vendedor', 'caja', 'mantenimiento', 'recursos_humanos', 'marketing', 'gerente'];

        for ($i = 7; $i <= 12; $i++) {
            $roleName = $roles[$i % count($roles)];
            $role = Role::where('name', $roleName)->first();
            $email = 'empleado' . $i . '@cesarcontrol.local';

            $empleado = Empleado::updateOrCreate(
                ['codigo' => 'EMP-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'nombre' => 'Empleado',
                    'apellidos' => 'Demo ' . $i,
                    'cargo' => ucfirst(str_replace('_', ' ', $roleName)),
                    'area' => ucfirst(str_replace('_', ' ', $roleName)),
                    'email' => $email,
                    'telefono' => '9000000' . $i,
                    'dni' => str_pad((string) (72000000 + $i), 8, '0', STR_PAD_LEFT),
                    'fecha_ingreso' => now()->subMonths($i)->toDateString(),
                    'tipo_contrato' => 'Indeterminado',
                    'horario' => '08:00 - 18:00',
                    'sueldo' => 1800 + ($i * 150),
                    'fecha_pago' => 30,
                    'banco' => 'Por registrar',
                    'cuenta_bancaria' => 'Por registrar',
                    'regimen_pensionario' => 'Por registrar',
                    'activo' => true,
                ]
            );

            if ($role) {
                User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $empleado->nombre . ' ' . $empleado->apellidos,
                        'password' => Hash::make(Str::password(32)),
                        'rol' => 'vendedor',
                        'employee_id' => $empleado->id,
                        'role_id' => $role->id,
                        'must_change_password' => true,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function creditsAndAccounts(): void
    {
        $clients = Cliente::where('tipo_cliente', '!=', 'anonimo')->limit(20)->get();

        foreach ($clients as $index => $client) {
            $credito = DB::table('creditos')->updateOrInsert(
                ['cliente_id' => $client->id, 'tipo' => 'credito'],
                [
                    'limite' => 1200 + ($index * 80),
                    'saldo_actual' => 100 + ($index * 25),
                    'fecha_vencimiento' => now()->addDays(15 + $index)->toDateString(),
                    'estado' => $index % 7 === 0 ? 'vencido' : 'activo',
                    'observacion' => 'Linea demo de desarrollo',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $accounts = Cliente::where('tipo_cliente', '!=', 'anonimo')->skip(5)->limit(15)->get();
        foreach ($accounts as $index => $client) {
            DB::table('creditos')->updateOrInsert(
                ['cliente_id' => $client->id, 'tipo' => 'cuenta'],
                [
                    'limite' => 0,
                    'saldo_actual' => 150 + ($index * 20),
                    'estado' => 'activo',
                    'observacion' => 'Dinero a cuenta demo',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function sales(): void
    {
        $target = 250;
        $current = Venta::count();
        if ($current >= $target) {
            return;
        }

        $clients = Cliente::where('tipo_cliente', '!=', 'anonimo')->get();
        $products = Producto::where('stock', '>', 0)->get();
        $sellers = User::whereHas('role', fn ($q) => $q->whereIn('name', ['vendedor', 'gerente']))->get();
        $methods = ['efectivo', 'yape', 'transferencia', 'tarjeta', 'credito', 'dinero_cuenta'];

        for ($i = $current + 1; $i <= $target; $i++) {
            $client = $clients[$i % max(1, $clients->count())];
            $seller = $sellers[$i % max(1, $sellers->count())] ?? null;
            $date = now()->subDays($i % 120)->setTime(9 + ($i % 9), ($i * 7) % 60);
            $method = $methods[$i % count($methods)];
            $subtotal = 0;

            $venta = Venta::create([
                'cliente_id' => $client->id,
                'vendedor_id' => $seller?->id,
                'subtotal' => 0,
                'igv' => 0,
                'descuento' => $i % 10 === 0 ? 8 : 0,
                'total' => 0,
                'estado' => $i % 13 === 0 ? 'pendiente_caja' : 'cobrada',
                'comprobante_tipo' => $i % 5 === 0 && $client->ruc ? 'factura' : 'boleta',
                'metodo_pago' => $method,
                'tipo_operacion' => $method === 'credito' ? 'prestamo' : ($method === 'dinero_cuenta' ? 'cuenta' : $method),
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            for ($j = 0; $j < 2 + ($i % 3); $j++) {
                $product = $products[($i + $j) % max(1, $products->count())];
                $quantity = 1 + (($i + $j) % 3);
                $line = round((float) $product->precio * $quantity, 2);
                $subtotal += $line;

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $product->id,
                    'cantidad' => $quantity,
                    'precio' => $product->precio,
                    'subtotal' => $line,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            $descuento = (float) $venta->descuento;
            $igv = round(max(0, $subtotal - $descuento) * 0.18, 2);
            $total = round(max(0, $subtotal - $descuento) + $igv, 2);
            $venta->update(['subtotal' => $subtotal, 'igv' => $igv, 'total' => $total]);

            if ($venta->estado === 'cobrada') {
                DB::table('venta_pagos')->insert([
                    'venta_id' => $venta->id,
                    'metodo' => $method,
                    'monto' => $total,
                    'estado' => in_array($method, ['yape', 'transferencia'], true) ? 'pendiente_validacion' : 'validado',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }

    private function cash(): void
    {
        $cashiers = User::whereHas('role', fn ($q) => $q->where('name', 'caja'))->get();

        if (DB::table('caja_aperturas')->count() < 20) {
            for ($i = 1; $i <= 20; $i++) {
                $date = now()->subDays($i);
                $id = DB::table('caja_aperturas')->insertGetId([
                    'codigo' => 'CAJA-DEMO-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'caja' => 'Caja principal',
                    'responsable_id' => $cashiers[$i % max(1, $cashiers->count())]?->id ?? null,
                    'abierta_at' => $date->copy()->setTime(8, 0),
                    'cerrada_at' => $date->copy()->setTime(18, 0),
                    'fondo_inicial' => 250,
                    'monto_contado' => 250 + ($i * 75),
                    'diferencia' => $i % 6 === 0 ? -5 : 0,
                    'estado' => $i % 6 === 0 ? 'pendiente_revision' : 'cerrada',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                foreach (['efectivo', 'yape', 'transferencia', 'tarjeta'] as $method) {
                    DB::table('caja_movimientos')->insert([
                        'caja_apertura_id' => $id,
                        'tipo' => 'ingreso',
                        'metodo' => $method,
                        'monto' => 120 + ($i * 10),
                        'categoria' => 'venta',
                        'descripcion' => 'Movimiento demo',
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }

        if (DB::table('caja_chica_movimientos')->count() < 20) {
            $cajas = DB::table('caja_aperturas')->pluck('id');

            for ($i = 1; $i <= 20; $i++) {
                DB::table('caja_movimientos')->insert([
                    'caja_apertura_id' => $cajas[$i % max(1, $cajas->count())],
                    'tipo' => $i % 5 === 0 ? 'reposicion' : 'egreso',
                    'metodo' => 'efectivo',
                    'monto' => 20 + ($i * 3),
                    'categoria' => $i % 2 === 0 ? 'movilidad' : 'insumos',
                    'descripcion' => 'Caja chica demo',
                    'created_at' => now()->subDays($i),
                    'updated_at' => now()->subDays($i),
                ]);

                DB::table('caja_chica_movimientos')->insert([
                    'caja_apertura_id' => $cajas[$i % max(1, $cajas->count())],
                    'tipo' => $i % 5 === 0 ? 'reposicion' : 'gasto',
                    'monto' => 20 + ($i * 3),
                    'categoria' => $i % 2 === 0 ? 'movilidad' : 'insumos',
                    'comprobante' => 'COMP-DEMO-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'descripcion' => 'Caja chica demo',
                    'created_at' => now()->subDays($i),
                    'updated_at' => now()->subDays($i),
                ]);
            }
        }
    }

    private function humanResources(): void
    {
        $employees = Empleado::limit(12)->get();
        foreach ($employees as $i => $employee) {
            for ($d = 0; $d < 10; $d++) {
                DB::table('empleado_asistencias')->updateOrInsert(
                    ['empleado_id' => $employee->id, 'fecha' => now()->subDays($d)->toDateString()],
                    [
                        'entrada' => $d % 7 === 0 ? '09:15:00' : '08:00:00',
                        'salida' => '18:00:00',
                        'estado' => $d % 9 === 0 ? 'permiso' : ($d % 7 === 0 ? 'tardanza' : 'presente'),
                        'observacion' => 'Registro demo',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            DB::table('empleado_movimientos_rrhh')->updateOrInsert(
                ['empleado_id' => $employee->id, 'tipo' => 'adelanto', 'fecha' => now()->subDays($i)->toDateString()],
                ['monto' => 80 + ($i * 10), 'estado' => 'registrado', 'descripcion' => 'Adelanto demo', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function maintenance(): void
    {
        $machines = [
            ['codigo' => 'MAQ-CAN-001', 'nombre' => 'Canteadora'],
            ['codigo' => 'MAQ-ESC-001', 'nombre' => 'Escuadradora'],
            ['codigo' => 'MAQ-RAN-001', 'nombre' => 'Escuadradora de ranuras'],
        ];

        foreach ($machines as $index => $machine) {
            DB::table('mantenimiento_maquinarias')->updateOrInsert(
                ['codigo' => $machine['codigo']],
                [
                    'nombre' => $machine['nombre'],
                    'tipo' => 'Produccion',
                    'marca' => 'Por registrar',
                    'modelo' => 'Por registrar',
                    'numero_serie' => 'Por registrar',
                    'ubicacion' => 'Taller',
                    'estado' => $index === 1 ? 'mantenimiento' : 'operativa',
                    'criticidad' => 'alta',
                    'horas_acumuladas' => 700 + ($index * 180),
                    'frecuencia_mantenimiento' => 'Mensual',
                    'ultimo_mantenimiento' => now()->subDays(20 + $index)->toDateString(),
                    'proximo_mantenimiento' => now()->addDays(10 - $index)->toDateString(),
                    'garantia' => 'Por registrar',
                    'observaciones' => 'Ficha inicial demo',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $machineIds = DB::table('mantenimiento_maquinarias')->pluck('id');
        $spares = ['Disco principal', 'Disco incisor', 'Cuchillas', 'Rodamientos', 'Correas', 'Fajas de arrastre', 'Filtros neumaticos', 'Sensores', 'Resistencias', 'Rodillos', 'Lubricantes', 'Botones electricos', 'Contactores', 'Fusibles', 'Guias', 'Mangueras', 'Valvulas', 'Interruptores', 'Aceite', 'Escobillas'];

        foreach ($spares as $i => $name) {
            DB::table('mantenimiento_repuestos')->updateOrInsert(
                ['codigo' => 'REP-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'nombre' => $name,
                    'maquinaria_id' => $machineIds[$i % max(1, $machineIds->count())],
                    'cantidad_disponible' => $i % 6,
                    'stock_minimo' => 2,
                    'unidad_medida' => 'unidad',
                    'proveedor' => 'Proveedor configurable',
                    'precio' => 25 + ($i * 8),
                    'ubicacion' => 'Almacen interno',
                    'estado' => $i % 6 === 0 ? 'agotado' : 'activo',
                    'ultimo_ingreso_at' => now()->subDays($i)->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if (DB::table('mantenimiento_registros')->count() < 30) {
            for ($i = 1; $i <= 30; $i++) {
                $machineId = $machineIds[$i % max(1, $machineIds->count())];
                DB::table('mantenimiento_registros')->insert([
                    'maquinaria_id' => $machineId,
                    'tipo' => $i % 4 === 0 ? 'correctivo' : 'preventivo',
                    'inicio_at' => now()->subDays($i * 2),
                    'fin_at' => now()->subDays($i * 2)->addHours(2 + ($i % 4)),
                    'duracion_horas' => 2 + ($i % 4),
                    'problema_encontrado' => 'Revision demo',
                    'actividad_realizada' => 'Limpieza, ajuste y prueba operativa',
                    'costo_mano_obra' => 80,
                    'costo_repuestos' => 40 + $i,
                    'estado_final' => 'operativa',
                    'recomendacion' => 'Mantener seguimiento',
                    'proxima_fecha' => now()->addDays(25 + $i)->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (DB::table('mantenimiento_fallas')->count() < 20) {
            for ($i = 1; $i <= 20; $i++) {
                DB::table('mantenimiento_fallas')->insert([
                    'maquinaria_id' => $machineIds[$i % max(1, $machineIds->count())],
                    'criticidad' => $i % 5 === 0 ? 'critica' : 'media',
                    'estado' => $i % 4 === 0 ? 'abierta' : 'cerrada',
                    'reportada_at' => now()->subDays($i),
                    'resuelta_at' => $i % 4 === 0 ? null : now()->subDays($i)->addHours(3),
                    'tiempo_inactividad_horas' => $i % 4 === 0 ? 2 : 3,
                    'descripcion' => 'Falla demo registrada',
                    'accion_realizada' => 'Revision y ajuste',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (DB::table('mantenimiento_pedidos_repuestos')->count() < 15) {
            $repuestos = DB::table('mantenimiento_repuestos')->pluck('id');
            for ($i = 1; $i <= 15; $i++) {
                DB::table('mantenimiento_pedidos_repuestos')->insert([
                    'repuesto_id' => $repuestos[$i % max(1, $repuestos->count())],
                    'maquinaria_id' => $machineIds[$i % max(1, $machineIds->count())],
                    'cantidad' => 1 + ($i % 4),
                    'urgencia' => $i % 5 === 0 ? 'critica' : 'media',
                    'motivo' => 'Reposicion demo para mantenimiento',
                    'estado' => ['solicitado', 'en_revision', 'aprobado', 'pedido', 'recibido'][$i % 5],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (DB::table('mantenimiento_cortes_energia')->count() < 8) {
            for ($i = 1; $i <= 8; $i++) {
                DB::table('mantenimiento_cortes_energia')->insert([
                    'fecha' => now()->subDays($i * 3)->toDateString(),
                    'hora_inicio' => '10:00:00',
                    'hora_retorno' => '10:45:00',
                    'duracion_horas' => 0.75,
                    'area_afectada' => 'Produccion',
                    'maquinarias_afectadas' => json_encode($machines),
                    'trabajo_interrumpido' => 'Corte de tablero',
                    'posible_causa' => 'Servicio electrico externo',
                    'accion_realizada' => 'Revision posterior de maquinaria',
                    'observaciones' => 'Registro demo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function marketing(): void
    {
        foreach (['Facebook', 'Instagram', 'TikTok', 'WhatsApp Business', 'YouTube'] as $name) {
            DB::table('marketing_redes_sociales')->updateOrInsert(
                ['nombre' => $name],
                [
                    'enlace' => 'https://example.com/' . Str::slug($name),
                    'usuario' => '@configurable',
                    'estado' => 'configurable',
                    'observacion' => 'Enlace demo configurable; no es una cuenta oficial.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        for ($i = 1; $i <= 8; $i++) {
            $campaignId = DB::table('marketing_campanas')->updateOrInsert(
                ['nombre' => 'Campana demo ' . $i],
                [
                    'estado' => $i % 3 === 0 ? 'activa' : 'planificada',
                    'inicio' => now()->subDays($i)->toDateString(),
                    'fin' => now()->addDays(20 + $i)->toDateString(),
                    'presupuesto' => 250 + ($i * 80),
                    'objetivo' => 'Impulsar productos con oportunidad comercial',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if (DB::table('marketing_publicaciones')->count() < 24) {
            for ($i = 1; $i <= 24; $i++) {
                DB::table('marketing_publicaciones')->insert([
                    'tipo' => ['grabacion', 'edicion', 'publicacion', 'evento'][$i % 4],
                    'titulo' => 'Contenido demo ' . $i,
                    'canal' => ['Facebook', 'Instagram', 'TikTok', 'YouTube'][$i % 4],
                    'programado_at' => now()->addDays($i),
                    'estado' => 'pendiente',
                    'observacion' => 'Actividad configurable',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
