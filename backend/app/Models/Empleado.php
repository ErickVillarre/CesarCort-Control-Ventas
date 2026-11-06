<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $fillable = [
        'codigo', 'nombre', 'apellidos', 'cargo', 'email', 'telefono', 'activo',
        'area', 'foto', 'dni', 'fecha_nacimiento', 'direccion', 'estado_civil',
        'contacto_emergencia', 'parentesco_emergencia', 'telefono_emergencia',
        'fecha_ingreso', 'tipo_contrato', 'horario', 'sueldo', 'fecha_pago',
        'banco', 'cuenta_bancaria', 'regimen_pensionario', 'cuspp',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function user()
    {
        return $this->hasOne(User::class, 'employee_id');
    }
}
