<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Apf3DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_small_synthetic_scenario_is_coherent_and_idempotent(): void
    {
        $oldFlag = getenv('APF3_DEMO_SEED');
        $oldPassword = getenv('DEMO_USER_PASSWORD');
        putenv('APF3_DEMO_SEED=true');
        putenv('DEMO_USER_PASSWORD=TestOnly-Strong-2026');
        $_ENV['APF3_DEMO_SEED'] = 'true';
        $_ENV['DEMO_USER_PASSWORD'] = 'TestOnly-Strong-2026';

        try {
            $this->seed(DatabaseSeeder::class);
            $this->seed(DatabaseSeeder::class);

            $this->assertDatabaseCount('productos', 12);
            $this->assertDatabaseCount('clientes', 5);
            $this->assertDatabaseCount('ventas', 4);
            $this->assertDatabaseCount('venta_pagos', 3);
            $this->assertDatabaseCount('creditos', 1);
            $this->assertDatabaseCount('credito_movimientos', 1);

            $this->assertEquals(10, DB::table('productos')->where('codigo', 'APF3-PROD-01')->value('stock'));
            $this->assertEquals(10, DB::table('productos')->where('codigo', 'APF3-PROD-02')->value('stock'));
            $this->assertEquals(26, DB::table('productos')->where('codigo', 'APF3-PROD-06')->value('stock'));
            $this->assertEquals(13, DB::table('productos')->where('codigo', 'APF3-PROD-08')->value('stock'));
            $this->assertEquals(118, DB::table('creditos')->value('saldo_actual'));
            $this->assertEquals(118, DB::table('clientes')->where('codigo_cliente', 'APF3-CLI-03')->value('saldo'));
        } finally {
            $oldFlag === false ? putenv('APF3_DEMO_SEED') : putenv('APF3_DEMO_SEED='.$oldFlag);
            $oldPassword === false ? putenv('DEMO_USER_PASSWORD') : putenv('DEMO_USER_PASSWORD='.$oldPassword);
            unset($_ENV['APF3_DEMO_SEED'], $_ENV['DEMO_USER_PASSWORD']);
        }
    }
}
