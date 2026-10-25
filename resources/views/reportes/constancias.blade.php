<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de constancias</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #102a43; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 18px 0 6px; }
        .muted { color: #627d98; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #bcccdc; padding: 5px 6px; vertical-align: top; }
        th { background: #243b53; color: #ffffff; font-weight: bold; }
        .num { text-align: right; }
        .qr { width: 68px; height: 68px; }
        .vacio { padding: 12px; background: #f0f4f8; }
    </style>
</head>
<body>
    <h1>Informe de solicitudes de constancias</h1>
    <p class="muted">
        Año {{ $anio }}
        @if ($nombreMes)
            · Mes: {{ $nombreMes }}
        @else
            · Todos los meses
        @endif
        · Generado {{ now()->format('Y-m-d H:i') }}
    </p>

    <h2>Constancias registradas por mes</h2>
    <table>
        <thead>
            <tr>
                <th>Mes</th>
                <th class="num">Solicitudes</th>
                <th class="num">Cantidad</th>
                <th class="num">Activas</th>
                <th class="num">Inactivas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($resumen as $fila)
                <tr>
                    <td>{{ $fila['nombre'] }}</td>
                    <td class="num">{{ $fila['solicitudes'] }}</td>
                    <td class="num">{{ $fila['cantidad'] }}</td>
                    <td class="num">{{ $fila['activas'] }}</td>
                    <td class="num">{{ $fila['inactivas'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total</strong></td>
                <td class="num"><strong>{{ collect($resumen)->sum('solicitudes') }}</strong></td>
                <td class="num"><strong>{{ collect($resumen)->sum('cantidad') }}</strong></td>
                <td class="num"><strong>{{ collect($resumen)->sum('activas') }}</strong></td>
                <td class="num"><strong>{{ collect($resumen)->sum('inactivas') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <h2>Detalle</h2>
    @if ($filas->isEmpty())
        <p class="vacio">No hay constancias registradas en el periodo seleccionado.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empleado</th>
                    <th>Correo</th>
                    <th>Móvil</th>
                    <th>Tipo</th>
                    <th class="num">Cantidad</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estado</th>
                    <th>QR</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        <td>{{ $fila['constancia']->id_constancias }}</td>
                        <td>{{ $fila['constancia']->nombre_empleado }}</td>
                        <td>{{ $fila['constancia']->correo }}</td>
                        <td>{{ $fila['constancia']->movil }}</td>
                        <td>{{ $fila['constancia']->tipo_de_constancia }}</td>
                        <td class="num">{{ $fila['constancia']->cantidad }}</td>
                        <td>{{ $fila['constancia']->fecha_de_solicitud?->format('Y-m-d') }}</td>
                        <td>{{ $fila['constancia']->hora_de_solicitud }}</td>
                        <td>{{ $fila['constancia']->estado_texto }}</td>
                        <td><img class="qr" src="{{ $fila['qr'] }}" alt="QR"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
