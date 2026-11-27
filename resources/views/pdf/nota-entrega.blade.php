<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 10mm 12mm 16mm;
        }

        body {
            font-family: 'DejaVu Sans', 'Segoe UI', Arial, sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.4;
        }

        .header {
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 8px;
            margin-bottom: 8px;
            width: 100%;
        }

        .header td {
            vertical-align: middle;
        }

        .header-logo {
            width: 17mm;
            text-align: left;
        }

        .header-logo img {
            max-width: 14mm;
            height: auto;
        }

        .header-logo--izquierda img {
            max-width: 17mm;
        }

        .header-center {
            text-align: center;
            padding: 0 3mm;
        }

        .line1 {
            font-size: 7.5pt;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            line-height: 1.3;
        }

        .dispatch-ref {
            width: 100%;
            border-bottom: 1px dashed #cbd5e1;
            padding: 2px 0 6px;
            margin-bottom: 6px;
        }

        .label {
            font-size: 7pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .code {
            font-size: 8pt;
            font-weight: 700;
            color: #1e3a5f;
        }

        .encabezado-central {
            font-size: 12pt;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            text-align: center;
        }

        .date {
            font-size: 8pt;
            color: #6b7280;
            text-align: right;
        }

        .info-table {
            width: 100%;
            border-spacing: 2px;
            margin-bottom: 6px;
        }

        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 6px;
            width: 50%;
            vertical-align: top;
        }

        .info-card h3 {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin: 0 0 3px;
            font-weight: 600;
        }

        .info-card .value {
            font-size: 10pt;
            font-weight: 600;
            color: #111827;
        }

        .info-card .meta {
            font-size: 7.5pt;
            color: #6b7280;
            margin-top: 1px;
        }

        .badge-interno {
            display: inline-block;
            background: #e0f2fe;
            color: #075985;
            border: 1px solid #bae6fd;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .observations {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px 7px;
            margin-bottom: 6px;
        }

        .observations h3 {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin: 0 0 3px;
            font-weight: 600;
        }

        .observations p {
            font-size: 9pt;
            color: #374151;
            margin: 0;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 18mm;
        }

        table.items thead th {
            background: #1e3a5f;
            color: #ffffff;
            padding: 4px 6px;
            text-align: left;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.items thead th:nth-child(2),
        table.items thead th:nth-child(3) {
            text-align: right;
        }

        table.items tbody td {
            padding: 4px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 8.5pt;
        }

        table.items tbody td:nth-child(2),
        table.items tbody td:nth-child(3) {
            text-align: right;
        }

        table.items tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .pagina-nota {
            position: relative;
            height: 260mm;
        }

        .pagina-nota--salto {
            page-break-after: always;
        }

        .firmas {
            position: absolute;
            bottom: 16mm;
            left: 0;
            right: 0;
            width: 100%;
            border-spacing: 10px 0;
        }

        .firma {
            text-align: center;
            width: 50%;
            font-size: 9pt;
        }

        .firma-nombre {
            font-weight: 600;
            color: #111827;
            border-top: 1px solid #1e3a5f;
            padding-top: 5px;
            margin-bottom: 2px;
        }

        .firma-rol {
            color: #6b7280;
            font-size: 8pt;
        }

        .pie-fijo {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1.5px solid #1e3a5f;
            text-align: center;
            padding-top: 4px;
            font-size: 6.5pt;
            color: #1e3a5f;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .pie-fijo div {
            line-height: 1.3;
        }

        .watermark {
            position: fixed;
            top: 45%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 56pt;
            font-weight: 700;
            color: rgba(220, 38, 38, 0.18);
            z-index: 0;
        }
    </style>
</head>
<body>
    @if($despacho->estado === \App\Models\Despacho::ESTADO_ANULADO)
        <div class="watermark">ANULADO</div>
    @endif

    @foreach ($paginas as $pagina)
        <div class="pagina-nota {{ $loop->last ? '' : 'pagina-nota--salto' }}">
            @if ($loop->first)
                <table class="header">
                    <tr>
                        <td class="header-logo header-logo--izquierda">
                            <img src="{{ public_path('images/logo-gob.png') }}" alt="Logo">
                        </td>
                        <td class="header-center">
                            <div class="line1">República Bolivariana de Venezuela</div>
                            <div class="line1">Gobernación Bolivariana del Estado Sucre</div>
                            <div class="line1">Servicio Autónomo de Protección Integral</div>
                            <div class="line1">al Niño, Niña y Adolescente del Estado Sucre</div>
                            <div class="line1">SAPINAES</div>
                        </td>
                        <td class="header-logo" style="text-align: right;">
                            <img src="{{ public_path('images/logo-sap.png') }}" alt="Logo institucional">
                        </td>
                    </tr>
                </table>

                <table class="dispatch-ref">
                    <tr>
                        <td style="width: 25%;">
                            <div class="label">Código</div>
                            <div class="code">{{ $despacho->numero }}</div>
                        </td>
                        <td class="encabezado-central">Nota de entrega</td>
                        <td class="date" style="width: 25%;">
                            Emitido: {{ $despacho->created_at?->format('d/m/Y h:i A') ?? $despacho->fecha_despacho?->format('d/m/Y') }}
                        </td>
                    </tr>
                </table>

                <table class="info-table">
                    <tr>
                        <td class="info-card">
                            <h3>Sede de origen</h3>
                            <div class="value">Almacén Central</div>
                            <div class="meta">Orden de despacho</div>
                        </td>
                        <td class="info-card">
                            <h3>Sede de destino</h3>
                            <div class="value">{{ $despacho->sede?->nombre ?? '—' }}</div>
                            <div class="meta">{{ $despacho->sede?->codigo ?? '—' }}</div>
                            @if($despacho->consumo_interno)
                                <div class="badge-interno">Consumo interno</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="info-card">
                            <h3>Persona que entrega</h3>
                            <div class="value">{{ $despacho->despachado_por_nombre ?? trim(($despacho->despachadoPor?->nombres ?? '').' '.($despacho->despachadoPor?->apellidos ?? '')) ?: '—' }}</div>
                        </td>
                        <td class="info-card">
                            <h3>Persona que recibe</h3>
                            <div class="value">{{ $despacho->recibido_por_nombre ?? trim(($despacho->recibidoPor?->nombres ?? '').' '.($despacho->recibidoPor?->apellidos ?? '')) ?: '—' }}</div>
                        </td>
                    </tr>
                </table>

                @if($despacho->observaciones)
                    <div class="observations">
                        <h3>Descripción / Novedades</h3>
                        <p>{{ $despacho->observaciones }}</p>
                    </div>
                @endif
            @endif

            <table class="items">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Unidad/de medida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pagina as $detalle)
                        <tr>
                            <td>{{ $detalle->producto_nombre ?? $detalle->producto?->nombre ?? '—' }}</td>
                            <td>{{ number_format($detalle->cantidad, 2, ',', '.') }}</td>
                            <td>{{ $detalle->producto?->unidad_medida ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($loop->last)
                <table class="firmas">
                    <tr>
                        <td class="firma">
                            <div class="firma-nombre">{{ $despacho->despachado_por_nombre ?? trim(($despacho->despachadoPor?->nombres ?? '').' '.($despacho->despachadoPor?->apellidos ?? '')) }}</div>
                            <div class="firma-rol">Entrega conforme</div>
                        </td>
                        <td class="firma">
                            <div class="firma-nombre">{{ $despacho->recibido_por_nombre ?? trim(($despacho->recibidoPor?->nombres ?? '').' '.($despacho->recibidoPor?->apellidos ?? '')) }}</div>
                            <div class="firma-rol">Recibe conforme</div>
                        </td>
                    </tr>
                </table>
            @endif
        </div>
    @endforeach

    <div class="pie-fijo">
        <div>Por la Protección Integral De Los Niños, Niñas Y Adolescentes.</div>
        <div>Av. Arismendi, Frente Al Cuartel "Antonio José de Sucre". Teléfono: 0293-4322706</div>
        <div>RIF. G – 20007058 – 7</div>
    </div>
</body>
</html>
