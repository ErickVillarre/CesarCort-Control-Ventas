<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'codigo_barras',
        'nombre',
        'precio',
        'costo',
        'stock',
        'tipo',
        'espesor',
        'canto_tipo',
        'canto_ancho',
        'color',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'costo' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
