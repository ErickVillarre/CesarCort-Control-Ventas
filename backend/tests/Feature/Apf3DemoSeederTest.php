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
        $demoEnvironment = [
            'APF3_DEMO_SEED' => 'true',
            'DEMO_USER_PASSWORD' => 'TestOnly-Strong-2026',
        ];
        $previousEnvironment = [];

        foreach ($demoEnvironment as $key => $value) {
            $previousEnvironment[$key] = [
                'process' => getenv($key),
                'env_exists' => array_key_exists($key, $_ENV),
                'env' => $_ENV[$key] ?? null,
                'server_exists' => array_key_exists($key, $_SERVER),
                'server' => $_SERVER[$key] ?? null,
            ];
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

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
            foreach ($previousEnvironment as $key => $previous) {
                $previous['process'] === false ? putenv($key) : putenv($key.'='.$previous['process']);
                if ($previous['env_exists']) {
                    $_ENV[$key] = $previous['env'];
                } else {
                    unset($_ENV[$key]);
                }
                if ($previous['server_exists']) {
                    $_SERVER[$key] = $previous['server'];
                } else {
                    unset($_SERVER[$key]);
                }
            }
        }
    }
}
