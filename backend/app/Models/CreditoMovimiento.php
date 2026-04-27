<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditoMovimiento extends Model
{
    protected $fillable = [
        'credito_id',
        'venta_id',
        'tipo_movimiento',
        'monto',
        'saldo_resultante',
        'observacion',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_resultante' => 'decimal:2',
    ];

    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}