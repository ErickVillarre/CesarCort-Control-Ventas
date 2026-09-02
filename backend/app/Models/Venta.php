<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'cliente_id',
        'vendedor_id',
        'caja_apertura_id',
        'subtotal',
        'igv',
        'descuento',
        'total',
        'estado',
        'comprobante_tipo',
        'metodo_pago',
        'tipo_operacion',
        'monto_recibido',
        'vuelto',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'vuelto' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }
}
