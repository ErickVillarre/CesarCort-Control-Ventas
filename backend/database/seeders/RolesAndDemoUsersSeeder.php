<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            $demoPassword = (string) env('DEMO_USER_PASSWORD', 'Temporal123!');

            $adminEmails = ['admin@gmail.com', 'superadmin@gmail.com', 'admin.full@cesarcontrol.local'];

            if ($adminRole) {
                $adminEmployee = Empleado::updateOrCreate(
                    ['email' => 'admin@gmail.com'],
                    [
                        'codigo' => 'EMP-ADM-001',
                        'nombre' => 'Administrador',
                        'apellidos' => 'Principal',
                        'cargo' => 'Administrador',
                        'area' => 'Administracion',
                        'telefono' => '900000000',
                        'activo' => true,
                    ]
                );

                User::updateOrCreate(
                    ['email' => 'admin@gmail.com'],
                    [
                        'name' => 'Administrador Principal',
                        'password' => Hash::make('123'),
                        'rol' => 'admin',
                        'employee_id' => $adminEmployee->id,
                        'role_id' => $adminRole->id,
                        'must_change_password' => false,
                        'is_active' => true,
                    ]
                );

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

                User::updateOrCreate(
                    ['email' => $demo['email']],
                    [
                        'name' => trim($demo['nombre'] . ' ' . $demo['apellidos']),
                        'password' => Hash::make($demoPassword),
                        'rol' => 'vendedor',
                        'employee_id' => $empleado->id,
                        'role_id' => $role->id,
                        'must_change_password' => true,
                        'is_active' => true,
                    ]
                );
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

        foreach ($this->requiredPermissionAliases() as $name => $meta) {
            $permissions[] = [
                'name' => $name,
                'label' => $meta['label'],
                'category' => $meta['category'],
            ];
        }

        return $permissions;
    }

    private function requiredPermissionAliases(): array
    {
        return [
            'users.view' => ['label' => 'Ver usuarios', 'category' => 'Administracion'],
            'users.create' => ['label' => 'Crear usuarios', 'category' => 'Administracion'],
            'users.update' => ['label' => 'Actualizar usuarios', 'category' => 'Administracion'],
            'users.deactivate' => ['label' => 'Desactivar usuarios', 'category' => 'Administracion'],
            'roles.view' => ['label' => 'Ver roles', 'category' => 'Administracion'],
            'roles.assign' => ['label' => 'Asignar roles', 'category' => 'Administracion'],
            'roles.manage' => ['label' => 'Gestionar roles', 'category' => 'Administracion'],
            'sales.view' => ['label' => 'Ver ventas', 'category' => 'Comercial'],
            'sales.create' => ['label' => 'Crear ventas', 'category' => 'Comercial'],
            'sales.update' => ['label' => 'Actualizar ventas', 'category' => 'Comercial'],
            'clients.view' => ['label' => 'Ver clientes', 'category' => 'Comercial'],
            'clients.create' => ['label' => 'Crear clientes', 'category' => 'Comercial'],
            'clients.update' => ['label' => 'Actualizar clientes', 'category' => 'Comercial'],
            'credits.view' => ['label' => 'Ver creditos', 'category' => 'Finanzas'],
            'inventory.view' => ['label' => 'Ver inventario', 'category' => 'Inventario'],
            'inventory.manage' => ['label' => 'Gestionar inventario', 'category' => 'Inventario'],
            'cash.view' => ['label' => 'Ver caja', 'category' => 'Caja'],
            'cash.open' => ['label' => 'Abrir caja', 'category' => 'Caja'],
            'cash.close' => ['label' => 'Cerrar caja', 'category' => 'Caja'],
            'maintenance.view' => ['label' => 'Ver mantenimiento', 'category' => 'Operaciones'],
            'maintenance.create' => ['label' => 'Crear mantenimiento', 'category' => 'Operaciones'],
            'maintenance.update' => ['label' => 'Actualizar mantenimiento', 'category' => 'Operaciones'],
            'maintenance.delete' => ['label' => 'Eliminar mantenimiento', 'category' => 'Operaciones'],
            'reports.view' => ['label' => 'Ver reportes', 'category' => 'Reportes'],
            'web_requests.view' => ['label' => 'Ver solicitudes web', 'category' => 'Marketing'],
            'web_requests.manage' => ['label' => 'Gestionar solicitudes web', 'category' => 'Marketing'],
        ];
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
                    'sales.view', 'sales.create', 'sales.update',
                    'clientes.ver', 'crm.ver', 'productos.ver', 'inventario.ver',
                    'clients.view', 'clients.create', 'clients.update', 'inventory.view',
                    'stock_alertas.ver', 'creditos.ver', 'creditos.aprobar',
                    'credits.view',
                    'creditos.editar', 'dinero_cuenta.ver', 'dinero_cuenta.editar',
                    'caja.ver', 'caja.aprobar',
                    'cash.view', 'cash.open', 'cash.close',
                    'gastos.ver', 'cuentas_cobrar.ver', 'cuentas_pagar.ver',
                    'empleados.ver', 'empleados.crear', 'empleados.editar',
                    'usuarios.gestionar', 'roles.ver', 'rrhh.ver', 'mantenimiento.ver',
                    'users.view', 'users.create', 'users.update', 'users.deactivate',
                    'roles.view', 'roles.assign',
                    'mantenimiento.aprobar', 'marketing.ver', 'reportes.ver',
                    'reportes.exportar', 'reports.view', 'web_requests.view', 'web_requests.manage',
                ],
            ],
            'vendedor' => [
                'label' => 'Vendedor',
                'description' => 'Ventas, clientes y seguimiento comercial',
                'permissions' => [
                    'dashboard.ver', 'ventas.ver', 'ventas.crear',
                    'sales.view', 'sales.create', 'sales.update',
                    'clientes.ver', 'clientes.crear', 'clientes.editar',
                    'clients.view', 'clients.create', 'clients.update',
                    'crm.ver', 'crm.crear', 'productos.ver', 'inventario.ver',
                    'inventory.view', 'creditos.ver', 'credits.view', 'dinero_cuenta.ver',
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
                    'cash.view', 'cash.open', 'cash.close', 'credits.view',
                    'gastos.ver', 'gastos.crear',
                ],
            ],
            'mantenimiento' => [
                'label' => 'Mantenimiento',
                'description' => 'Maquinaria, fallas, repuestos y cortes de energia',
                'permissions' => [
                    'dashboard.ver', 'mantenimiento.ver', 'mantenimiento.crear',
                    'mantenimiento.editar', 'maintenance.view', 'maintenance.create',
                    'maintenance.update', 'maintenance.delete',
                ],
            ],
            'rrhh' => [
                'label' => 'RRHH',
                'description' => 'Gestion del personal',
                'permissions' => [
                    'dashboard.ver', 'empleados.ver', 'empleados.crear',
                    'empleados.editar', 'asistencia.ver', 'asistencia.crear',
                    'rrhh.ver', 'rrhh.crear', 'rrhh.editar',
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
                    'inventory.view', 'web_requests.view', 'web_requests.manage',
                    'stock_alertas.ver', 'marketing.ver', 'marketing.crear',
                    'marketing.editar',
                ],
            ],
            'cliente' => [
                'label' => 'Cliente',
                'description' => 'Acceso limitado para consultas futuras del portal',
                'permissions' => [],
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
            ['codigo' => 'EMP-005', 'nombre' => 'Rosa', 'apellidos' => 'Fernandez', 'cargo' => 'Recursos Humanos', 'area' => 'Personal', 'role' => 'rrhh', 'email' => 'rrhh@cesarcontrol.local', 'telefono' => '900000005'],
            ['codigo' => 'EMP-006', 'nombre' => 'Marco', 'apellidos' => 'Castillo', 'cargo' => 'Marketing', 'area' => 'Marketing', 'role' => 'marketing', 'email' => 'marketing@cesarcontrol.local', 'telefono' => '900000006'],
            ['codigo' => 'EMP-CLI-001', 'nombre' => 'Cliente', 'apellidos' => 'Portal', 'cargo' => 'Cliente', 'area' => 'Portal', 'role' => 'cliente', 'email' => 'cliente@cesarcontrol.local', 'telefono' => '900000007'],
        ];
    }
}
