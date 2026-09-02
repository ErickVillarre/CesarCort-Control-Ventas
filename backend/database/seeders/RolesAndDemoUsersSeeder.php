<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolesAndDemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $permissions = $this->permissions();

            foreach ($permissions as $permission) {
                Permission::updateOrCreate(
                    ['name' => $permission['name']],
                    ['label' => $permission['label'], 'category' => $permission['category']]
                );
            }

            foreach ($this->roles(array_column($permissions, 'name')) as $name => $data) {
                $role = Role::updateOrCreate(
                    ['name' => $name],
                    ['label' => $data['label'], 'description' => $data['description']]
                );

                $role->permissions()->sync(
                    Permission::whereIn('name', $data['permissions'])->pluck('id')->all()
                );
            }

            $adminRole = Role::where('name', 'admin')->first();
            $vendedorRole = Role::where('name', 'vendedor')->first();

            $adminEmails = ['admin@gmail.com', 'superadmin@gmail.com', 'admin.full@cesarcontrol.local'];

            if ($adminRole) {
                User::whereIn('email', $adminEmails)->update([
                    'rol' => 'admin',
                    'role_id' => $adminRole->id,
                    'is_active' => true,
                ]);

                User::where('rol', 'admin')
                    ->whereNotIn('email', $adminEmails)
                    ->update(['rol' => 'vendedor']);

                User::whereNotIn('email', $adminEmails)
                    ->where('role_id', $adminRole->id)
                    ->update([
                        'rol' => 'vendedor',
                        'role_id' => $vendedorRole?->id,
                    ]);
            }

            foreach ($this->demoUsers() as $demo) {
                $empleado = Empleado::updateOrCreate(
                    ['email' => $demo['email']],
                    [
                        'codigo' => $demo['codigo'],
                        'nombre' => $demo['nombre'],
                        'apellidos' => $demo['apellidos'],
                        'cargo' => $demo['cargo'],
                        'area' => $demo['area'],
                        'telefono' => $demo['telefono'],
                        'activo' => true,
                    ]
                );

                $role = Role::where('name', $demo['role'])->firstOrFail();

                User::firstOrCreate(
                    ['email' => $demo['email']],
                    [
                        'name' => trim($demo['nombre'] . ' ' . $demo['apellidos']),
                        'password' => Hash::make(Str::password(32)),
                        'rol' => 'vendedor',
                        'employee_id' => $empleado->id,
                        'role_id' => $role->id,
                        'must_change_password' => true,
                        'is_active' => true,
                    ]
                );

                User::where('email', $demo['email'])->update([
                    'name' => trim($demo['nombre'] . ' ' . $demo['apellidos']),
                    'employee_id' => $empleado->id,
                    'role_id' => $role->id,
                    'is_active' => true,
                ]);
            }
        });
    }

    private function permissions(): array
    {
        $modules = [
            'dashboard' => 'Principal',
            'ventas' => 'Comercial',
            'clientes' => 'Comercial',
            'crm' => 'CRM',
            'productos' => 'Inventario',
            'inventario' => 'Inventario',
            'stock_alertas' => 'Inventario',
            'creditos' => 'Finanzas',
            'dinero_cuenta' => 'Finanzas',
            'caja' => 'Caja',
            'gastos' => 'Finanzas',
            'cuentas_cobrar' => 'Finanzas',
            'cuentas_pagar' => 'Finanzas',
            'empleados' => 'Personal',
            'asistencia' => 'Personal',
            'rrhh' => 'Personal',
            'mantenimiento' => 'Operaciones',
            'marketing' => 'Marketing',
            'reportes' => 'Reportes',
            'usuarios' => 'Administracion',
            'roles' => 'Administracion',
            'configuracion' => 'Configuracion',
        ];

        $actions = [
            'ver' => 'Ver',
            'crear' => 'Crear',
            'editar' => 'Editar',
            'aprobar' => 'Aprobar',
            'cobrar' => 'Cobrar',
            'cerrar' => 'Cerrar',
            'anular' => 'Anular',
            'exportar' => 'Exportar',
            'desactivar' => 'Desactivar',
            'gestionar' => 'Gestionar',
        ];

        $permissions = [];
        foreach ($modules as $module => $category) {
            foreach ($actions as $action => $label) {
                $permissions[] = [
                    'name' => "{$module}.{$action}",
                    'label' => "{$label} " . str_replace('_', ' ', $module),
                    'category' => $category,
                ];
            }
        }

        $permissions[] = ['name' => 'caja.cobrar_sin_apertura', 'label' => 'Cobrar sin caja abierta', 'category' => 'Caja'];

        return $permissions;
    }

    private function roles(array $allPermissions): array
    {
        return [
            'admin' => [
                'label' => 'Administrador',
                'description' => 'Acceso completo al sistema',
                'permissions' => $allPermissions,
            ],
            'gerente' => [
                'label' => 'Gerente',
                'description' => 'Supervision integral y aprobaciones',
                'permissions' => [
                    'dashboard.ver', 'ventas.ver', 'ventas.crear', 'ventas.aprobar',
                    'clientes.ver', 'crm.ver', 'productos.ver', 'inventario.ver',
                    'stock_alertas.ver', 'creditos.ver', 'creditos.aprobar',
                    'creditos.editar', 'dinero_cuenta.ver', 'dinero_cuenta.editar',
                    'caja.ver', 'caja.aprobar',
                    'gastos.ver', 'cuentas_cobrar.ver', 'cuentas_pagar.ver',
                    'empleados.ver', 'rrhh.ver', 'mantenimiento.ver',
                    'mantenimiento.aprobar', 'marketing.ver', 'reportes.ver',
                    'reportes.exportar',
                ],
            ],
            'vendedor' => [
                'label' => 'Vendedor',
                'description' => 'Ventas, clientes y seguimiento comercial',
                'permissions' => [
                    'dashboard.ver', 'ventas.ver', 'ventas.crear',
                    'clientes.ver', 'clientes.crear', 'clientes.editar',
                    'crm.ver', 'crm.crear', 'productos.ver', 'inventario.ver',
                    'creditos.ver', 'dinero_cuenta.ver',
                    'stock_alertas.ver', 'stock_alertas.crear',
                ],
            ],
            'caja' => [
                'label' => 'Caja',
                'description' => 'Cobros, caja diaria y caja chica',
                'permissions' => [
                    'dashboard.ver', 'ventas.ver', 'creditos.ver',
                    'dinero_cuenta.ver', 'dinero_cuenta.crear',
                    'caja.ver', 'caja.crear', 'caja.cobrar', 'caja.cerrar',
                    'gastos.ver', 'gastos.crear',
                ],
            ],
            'mantenimiento' => [
                'label' => 'Mantenimiento',
                'description' => 'Maquinaria, fallas, repuestos y cortes de energia',
                'permissions' => [
                    'dashboard.ver', 'mantenimiento.ver', 'mantenimiento.crear',
                    'mantenimiento.editar',
                ],
            ],
            'recursos_humanos' => [
                'label' => 'Recursos Humanos',
                'description' => 'Gestion del personal',
                'permissions' => [
                    'dashboard.ver', 'empleados.ver', 'empleados.crear',
                    'empleados.editar', 'asistencia.ver', 'asistencia.crear',
                    'rrhh.ver', 'rrhh.crear', 'rrhh.editar',
                ],
            ],
            'marketing' => [
                'label' => 'Marketing',
                'description' => 'Campanas, redes y tendencias',
                'permissions' => [
                    'dashboard.ver', 'productos.ver', 'inventario.ver',
                    'stock_alertas.ver', 'marketing.ver', 'marketing.crear',
                    'marketing.editar',
                ],
            ],
        ];
    }

    private function demoUsers(): array
    {
        return [
            ['codigo' => 'EMP-001', 'nombre' => 'Ana', 'apellidos' => 'Torres', 'cargo' => 'Gerente', 'area' => 'Gerencia', 'role' => 'gerente', 'email' => 'gerencia@cesarcontrol.local', 'telefono' => '900000001'],
            ['codigo' => 'EMP-002', 'nombre' => 'Luis', 'apellidos' => 'Mendoza', 'cargo' => 'Asesor de ventas', 'area' => 'Comercial', 'role' => 'vendedor', 'email' => 'ventas@cesarcontrol.local', 'telefono' => '900000002'],
            ['codigo' => 'EMP-003', 'nombre' => 'Carla', 'apellidos' => 'Rojas', 'cargo' => 'Responsable de caja', 'area' => 'Caja', 'role' => 'caja', 'email' => 'caja@cesarcontrol.local', 'telefono' => '900000003'],
            ['codigo' => 'EMP-004', 'nombre' => 'Diego', 'apellidos' => 'Salazar', 'cargo' => 'Tecnico de mantenimiento', 'area' => 'Mantenimiento', 'role' => 'mantenimiento', 'email' => 'mantenimiento@cesarcontrol.local', 'telefono' => '900000004'],
            ['codigo' => 'EMP-005', 'nombre' => 'Rosa', 'apellidos' => 'Fernandez', 'cargo' => 'Recursos Humanos', 'area' => 'Personal', 'role' => 'recursos_humanos', 'email' => 'rrhh@cesarcontrol.local', 'telefono' => '900000005'],
            ['codigo' => 'EMP-006', 'nombre' => 'Marco', 'apellidos' => 'Castillo', 'cargo' => 'Marketing', 'area' => 'Marketing', 'role' => 'marketing', 'email' => 'marketing@cesarcontrol.local', 'telefono' => '900000006'],
        ];
    }
}
