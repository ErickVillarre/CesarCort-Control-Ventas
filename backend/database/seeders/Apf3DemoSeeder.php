<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\CreditoMovimiento;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** A small, repeatable scenario for an isolated local/testing database only. */
class Apf3DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing']) || !filter_var(env('APF3_DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new \RuntimeException('APF3 demo solo se permite en local/testing con APF3_DEMO_SEED=true.');
        }

        if (Producto::whereNull('codigo')->orWhere('codigo', 'not like', 'APF3-%')->exists()
            || Cliente::whereNull('codigo_cliente')->orWhere('codigo_cliente', 'not like', 'APF3-%')->exists()
            || Venta::whereNull('referencia_demo')->exists()) {
            throw new \RuntimeException('La base contiene datos ajenos a APF3. Use una base de demostración separada.');
        }

        $this->call(RolesAndDemoUsersSeeder::class);

        DB::transaction(function () {
            $seller = User::where('email', 'ventas.demo@cesarcontrol.test')->firstOrFail();
            $this->products();
            $this->clients();

            $this->sale('APF3-VENTA-01', 'APF3-CLI-01', 'APF3-PROD-01', 2, 'efectivo', 'contado', $seller, 3);
            $this->sale('APF3-VENTA-02', 'APF3-CLI-02', 'APF3-PROD-06', 4, 'efectivo', 'contado', $seller, 2);
            $this->sale('APF3-VENTA-03', 'APF3-CLI-03', 'APF3-PROD-08', 2, 'credito', 'prestamo', $seller, 1);
            $this->sale('APF3-VENTA-04', 'APF3-CLI-04', 'APF3-PROD-02', 1, 'efectivo', 'contado', $seller, 0, false);
        });
    }

    private function products(): void
    {
        $rows = [
            ['Tablero melamina blanco 18 mm', 'melamina', 200.00, 125.00, 12],
            ['Tablero melamina roble claro 18 mm', 'melamina', 245.00, 158.00, 10],
            ['Tablero melamina grafito 18 mm', 'melamina', 255.00, 168.00, 9],
            ['Tablero melamina nogal 15 mm', 'melamina', 218.00, 140.00, 8],
            ['Canto PVC blanco delgado', 'canto', 3.50, 1.80, 35],
            ['Canto PVC roble grueso', 'canto', 5.00, 2.70, 30],
            ['Bisagra cierre suave', 'accesorio', 18.00, 9.00, 24],
            ['Corredera telescópica', 'accesorio', 50.00, 28.00, 15],
            ['Tornillos para melamina (bolsa)', 'accesorio', 12.00, 6.00, 20],
            ['Servicio de corte de tablero', 'servicio', 8.00, 0.00, 0],
            ['Servicio de canteado por metro', 'servicio', 4.00, 0.00, 0],
            ['Retazo de melamina surtido', 'melamina', 15.00, 5.00, 18],
        ];

        foreach ($rows as $index => [$nombre, $tipo, $precio, $costo, $stock]) {
            Producto::firstOrCreate(['codigo' => sprintf('APF3-PROD-%02d', $index + 1)], [
                'nombre' => $nombre,
                'tipo' => $tipo,
                'precio' => $precio,
                'costo' => $costo,
                'stock' => $stock,
            ]);
        }
    }

    private function clients(): void
    {
        foreach ([
            ['APF3-CLI-01', 'Taller Demo Norte', 'regular'],
            ['APF3-CLI-02', 'Estudio Demo Interior', 'regular'],
            ['APF3-CLI-03', 'Carpintería Demo Central', 'credito'],
            ['APF3-CLI-04', 'Proyecto Demo Cocina', 'regular'],
            ['APF3-CLI-05', 'Mueblería Demo Sur', 'regular'],
        ] as [$codigo, $nombre, $tipo]) {
            Cliente::firstOrCreate(['codigo_cliente' => $codigo], [
                'nombre' => $nombre,
                'tipo_cliente' => $tipo,
                'credito' => $tipo === 'credito' ? 600 : 0,
                'saldo' => 0,
                'activo' => true,
            ]);
        }
    }

    private function sale(
        string $reference,
        string $clientCode,
        string $productCode,
        int $quantity,
        string $paymentMethod,
        string $operationType,
        User $seller,
        int $daysAgo,
        bool $paid = true
    ): void {
        if (Venta::where('referencia_demo', $reference)->exists()) {
            return;
        }

        $client = Cliente::where('codigo_cliente', $clientCode)->firstOrFail();
        $product = Producto::where('codigo', $productCode)->firstOrFail();
        $subtotal = round((float) $product->precio * $quantity, 2);
        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);
        $when = now()->subDays($daysAgo);

        $sale = Venta::create([
            'referencia_demo' => $reference,
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'descuento' => 0,
            'total' => $total,
            'estado' => $paid ? 'cobrada' : 'pendiente_caja',
            'comprobante_tipo' => 'interno',
            'metodo_pago' => $paymentMethod,
            'tipo_operacion' => $operationType,
            'monto_recibido' => $paid && $paymentMethod === 'efectivo' ? $total : null,
            'vuelto' => 0,
        ]);
        $sale->forceFill(['created_at' => $when, 'updated_at' => $when])->save();
        DetalleVenta::create([
            'venta_id' => $sale->id,
            'producto_id' => $product->id,
            'cantidad' => $quantity,
            'precio' => $product->precio,
            'subtotal' => $subtotal,
        ]);

        if (!$paid) {
            return;
        }

        if ($product->tipo !== 'servicio') {
            $product->decrement('stock', $quantity);
        }

        DB::table('venta_pagos')->insert([
            'venta_id' => $sale->id,
            'metodo' => $paymentMethod,
            'monto' => $total,
            'estado' => 'validado',
            'validado_por' => $seller->id,
            'validado_at' => $when,
            'created_at' => $when,
            'updated_at' => $when,
        ]);

        if ($paymentMethod === 'credito') {
            $credit = Credito::firstOrCreate(['cliente_id' => $client->id, 'tipo' => 'credito', 'estado' => 'activo'], [
                'limite' => 600,
                'saldo_actual' => 0,
                'fecha_vencimiento' => now()->addDays(30)->toDateString(),
                'observacion' => 'Línea sintética APF3',
                'creado_por' => $seller->id,
            ]);
            $credit->increment('saldo_actual', $total);
            $client->increment('saldo', $total);
            CreditoMovimiento::create([
                'credito_id' => $credit->id,
                'venta_id' => $sale->id,
                'tipo_movimiento' => 'cargo',
                'monto' => $total,
                'saldo_resultante' => $credit->fresh()->saldo_actual,
                'observacion' => 'Venta sintética APF3',
                'user_id' => $seller->id,
            ]);
        }
    }
}
