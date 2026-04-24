<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio',
        'subtotal'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function venta()
    {
    return $this->belongsTo(Venta::class);
    }
}
