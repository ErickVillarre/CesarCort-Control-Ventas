<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
    </style>
</head>
<body>

<h2>BOLETA DE VENTA</h2>

<p><strong>Cliente:</strong> {{ $venta->cliente->nombre}}
<p><strong>Fecha:</strong> {{ $venta->created_at }}</p>

<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cant</th>
            <th>Precio</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($venta->detalles as $item)
        <tr>
            <td>{{ $item->producto->nombre }}</td>
            <td>{{ $item->cantidad }}</td>
            <td>{{ $item->precio }}</td>
            <td>{{ $item->subtotal }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<br>

<p>Subtotal: S/ {{ number_format($subtotal, 2) }}</p>
<p>IGV (18%): S/ {{ number_format($igv, 2) }}</p>
<p><strong>Total: S/ {{ $venta->total }}</strong></p>

</body>
</html>