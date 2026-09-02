<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $fillable = [
        'codigo',
        'foto',
        'nombre',
        'apellidos',
        'dni',
        'fecha_nacimiento',
        'cargo',
        'area',
        'email',
        'telefono',
        'direccion',
        'estado_civil',
        'contacto_emergencia',
        'parentesco_emergencia',
        'telefono_emergencia',
        'fecha_ingreso',
        'tipo_contrato',
        'horario',
        'sueldo',
        'fecha_pago',
        'banco',
        'cuenta_bancaria',
        'regimen_pensionario',
        'cuspp',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'sueldo' => 'decimal:2',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'employee_id');
    }
}
