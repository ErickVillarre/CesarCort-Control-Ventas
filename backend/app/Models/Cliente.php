<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'nombre',
        'apodo',
        'dni',
        'telefono',
        'email',
        'direccion',
        'dni_imagen',
        'firma_imagen',
        'tipo_cliente',
        'activo',
        'credito',
        'saldo',
    ];

    protected $casts = [
        'credito' => 'decimal:2',
        'saldo' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function creditos()
    {
        return $this->hasMany(Credito::class);
    }
}