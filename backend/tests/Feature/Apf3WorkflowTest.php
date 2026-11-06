<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Apf3WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_require_login_and_access_decisions_do_not_grant_permissions(): void
    {
        $seller = $this->userWithRole('vendedor');
        $manager = $this->userWithRole('gerente', ['solicitudes.gestionar']);
        $otherSeller = $this->userWithRole('vendedor');

        $this->getJson('/api/solicitudes')->assertUnauthorized();

        Sanctum::actingAs($seller);
        $created = $this->postJson('/api/solicitudes', [
            'tipo' => 'acceso',
            'asunto' => 'Acceso a reportes',
            'descripcion' => 'Necesito consultar el resumen mensual.',
            'prioridad' => 'media',
        ])->assertCreated()->assertJsonPath('estado', 'pendiente');
        $id = $created->json('id');
        $this->patchJson("/api/solicitudes/{$id}/resolver", [
            'decision' => 'aprobada',
            'resolucion' => 'Autorizado para revisión administrativa.',
        ])->assertForbidden();

        Sanctum::actingAs($otherSeller);
        $this->getJson("/api/solicitudes/{$id}")->assertForbidden();
        $this->getJson('/api/solicitudes')->assertOk()->assertJsonCount(0);

        Sanctum::actingAs($manager);
        $this->getJson('/api/solicitudes')->assertOk()->assertJsonCount(1);
        $this->patchJson("/api/solicitudes/{$id}/asignar", [
            'responsable_id' => $manager->id,
        ])->assertOk()->assertJsonPath('estado', 'en_revision');
        $this->patchJson("/api/solicitudes/{$id}/resolver", [
            'decision' => 'aprobada',
            'resolucion' => 'Decisión registrada; el alta de permisos sigue otro proceso.',
        ])->assertOk()->assertJsonPath('estado', 'resuelta');

        $this->assertSame('vendedor', $seller->fresh()->role_name);
        $this->assertFalse($seller->fresh()->hasPermission('reportes.ver'));
        $this->assertDatabaseCount('solicitud_eventos', 3);
        $this->patchJson("/api/solicitudes/{$id}/resolver", [
            'decision' => 'rechazada',
            'resolucion' => 'Intento duplicado.',
        ])->assertStatus(409);
    }

    public function test_login_and_finalized_sale_update_stock_once_and_enforce_role_access(): void
    {
        $admin = $this->userWithRole('admin');
        $seller = $this->userWithRole('vendedor', ['productos.ver', 'clientes.ver', 'ventas.ver', 'ventas.crear']);
        $client = Cliente::create(['nombre' => 'Cliente de prueba', 'tipo_cliente' => 'regular']);
        $product = Producto::create(['nombre' => 'Tablero demo', 'precio' => 100, 'stock' => 5, 'tipo' => 'melamina']);

        $this->postJson('/api/login', ['email' => $seller->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.role_name', 'vendedor');

        Sanctum::actingAs($seller);
        $this->getJson('/api/productos')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/clientes')->assertOk();
        $this->postJson('/api/productos', ['tipo' => 'melamina', 'nombre' => 'Otro', 'precio' => 10])
            ->assertForbidden();

        $sale = $this->postJson('/api/ventas', $this->saleData($client->id, $product->id))
            ->assertCreated()->assertJsonPath('estado', 'pendiente_caja');
        $this->assertEquals(5, $product->fresh()->stock);

        Sanctum::actingAs($admin);
        $this->getJson('/api/ventas')->assertOk();
        $this->postJson('/api/ventas', $this->saleData($client->id, $product->id, true))
            ->assertCreated()->assertJsonPath('estado', 'cobrada');
        $this->assertEquals(3, $product->fresh()->stock);
        $this->assertDatabaseHas('ventas', ['id' => $sale->json('id'), 'estado' => 'pendiente_caja']);
        $this->assertDatabaseHas('venta_pagos', ['metodo' => 'efectivo', 'estado' => 'validado']);
    }

    public function test_cashier_collects_pending_sale_once_and_validates_transfer_before_stock_changes(): void
    {
        $seller = $this->userWithRole('vendedor', ['ventas.crear']);
        $cashier = $this->userWithRole('caja', ['caja.ver', 'caja.crear', 'caja.cobrar', 'caja.cerrar']);
        $client = Cliente::create(['nombre' => 'Cliente de caja', 'tipo_cliente' => 'regular']);
        $product = Producto::create(['nombre' => 'Canto de prueba', 'precio' => 100, 'stock' => 5, 'tipo' => 'canto']);

        Sanctum::actingAs($seller);
        $sale = $this->postJson('/api/ventas', $this->saleData($client->id, $product->id))
            ->assertCreated()->json();

        Sanctum::actingAs($cashier);
        $this->postJson('/api/caja/abrir', [
            'caja' => 'Caja de prueba', 'fondo_inicial' => 50,
        ])->assertCreated();
        $this->getJson('/api/caja/dashboard')->assertOk()->assertJsonCount(1, 'preventas_pendientes');

        $payment = $this->postJson("/api/caja/ventas/{$sale['id']}/cobrar", [
            'pagos' => [['metodo' => 'transferencia', 'monto' => 236, 'numero_operacion' => 'TEST-001']],
        ])->assertOk()->assertJsonPath('estado', 'pendiente_caja');
        $this->assertEquals(5, $product->fresh()->stock);

        $paymentId = DB::table('venta_pagos')->where('venta_id', $sale['id'])->value('id');
        $this->postJson("/api/caja/ventas/{$sale['id']}/cobrar", [
            'pagos' => [['metodo' => 'efectivo', 'monto' => 236]],
        ])->assertStatus(409);
        $this->postJson("/api/caja/pagos/{$paymentId}/validar", ['estado' => 'validado'])
            ->assertOk()->assertJsonPath('estado', 'validado');
        $this->assertDatabaseHas('ventas', ['id' => $sale['id'], 'estado' => 'cobrada']);
        $this->assertEquals(3, $product->fresh()->stock);
        $this->postJson("/api/caja/pagos/{$paymentId}/validar", ['estado' => 'validado'])->assertStatus(409);
        $this->postJson("/api/caja/ventas/{$sale['id']}/cobrar", [
            'pagos' => [['metodo' => 'efectivo', 'monto' => 236]],
        ])->assertStatus(409);
        $this->assertEquals(3, $product->fresh()->stock);
    }

    private function userWithRole(string $roleName, array $permissions = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)]);
        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], [
                'label' => $name,
                'category' => 'Pruebas',
            ]);
            $role->permissions()->syncWithoutDetaching($permission->id);
        }

        return User::factory()->create([
            'role_id' => $role->id,
            'rol' => $roleName === 'admin' ? 'admin' : 'vendedor',
            'is_active' => true,
        ]);
    }

    private function saleData(int $clientId, int $productId, bool $finalize = false): array
    {
        return [
            'cliente_id' => $clientId,
            'productos' => [['producto_id' => $productId, 'cantidad' => 2, 'precio' => 100]],
            'metodo_pago' => 'efectivo',
            'tipo_operacion' => 'contado',
            'comprobante_tipo' => 'interno',
            'monto_recibido' => 250,
            'finalizar' => $finalize,
        ];
    }
}
