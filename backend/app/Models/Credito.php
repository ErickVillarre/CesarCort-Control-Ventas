<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credito extends Model
{
    protected $fillable = [
        'cliente_id',
        'tipo',
        'limite',
        'saldo_actual',
        'fecha_vencimiento',
        'estado',
        'observacion',
        'creado_por',
    ];

    protected $casts = [
        'limite' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function movimientos()
    {
        return $this->hasMany(CreditoMovimiento::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}