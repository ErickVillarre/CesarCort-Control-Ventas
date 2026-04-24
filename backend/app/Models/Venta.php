<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
    'cliente_id',
    'total',
    'metodo_pago',
    'tipo_operacion'
];
public function cliente()
{
    return $this->belongsTo(Cliente::class);
}



public function detalles()
{
    return $this->hasMany(DetalleVenta::class, 'venta_id', 'id');
}
}
