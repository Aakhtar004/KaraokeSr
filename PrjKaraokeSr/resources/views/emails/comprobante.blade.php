<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 14px;
            color: #666;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .client-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .total-row {
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name"><span style="color: #b30000;">Restobar Karaoke Salon Rojo</span></div>
        <div class="company-info">
            Gral Deustua 160, Tacna 23001<br>
            Teléfono: (+51) 914472309<br>
        </div>
    </div>

    <div class="document-title">
        {{ $comprobante->tipo_comprobante == 'factura' ? 'FACTURA ELECTRÓNICA' : 'BOLETA ELECTRÓNICA' }}<br>
        {{ $comprobante->serie_comprobante }}-{{ $comprobante->numero_correlativo_comprobante }}
    </div>

    <div class="client-info">
        <strong>Cliente:</strong> {{ $comprobante->nombre_razon_social_cliente }}<br>
        <strong>{{ $comprobante->tipo_documento_cliente }}:</strong> {{ $comprobante->numero_documento_cliente }}<br>
        <strong>Fecha de emisión:</strong> 
        @if($comprobante->fecha_emision instanceof \Carbon\Carbon)
            {{ $comprobante->fecha_emision->format('d/m/Y H:i:s') }}
        @else
            {{ \Carbon\Carbon::parse($comprobante->fecha_emision)->format('d/m/Y H:i:s') }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Cant.</th>
                <th>Descripción</th>
                <th>P. Unit</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($comprobante->pedido->detalles as $detalle)
            <tr>
                <td>{{ $detalle->cantidad }}</td>
                <td>{{ $detalle->producto->nombre }}</td>
                <td class="text-right">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                <td class="text-right">S/ {{ number_format($detalle->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>Subtotal: S/ {{ number_format($comprobante->subtotal_comprobante, 2) }}</p>
        <p>IGV ({{ $comprobante->igv_aplicado_tasa }}%): S/ {{ number_format($comprobante->monto_igv, 2) }}</p>
        <p class="total-row">TOTAL: S/ {{ number_format($comprobante->monto_total_comprobante, 2) }}</p>
    </div>

    <div class="footer">
        <p><strong>🎤 Muchas gracias por su visita a <span style="color: #b30000;">Restobar Karaoke "Salón Rojo"</span> 😊</strong></p>
        <p>Esperamos que haya sido de su agrado.</p>
        <p>¡Vuelva pronto y disfrute nuevamente con nosotros!</p>
    </div>
</body>
</html>