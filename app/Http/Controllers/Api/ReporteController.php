<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudConstancia;
use App\Services\QrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(private readonly QrService $qr)
    {
    }

    public function pdf(Request $request)
    {
        ['anio' => $anio, 'mes' => $mes] = $this->periodo($request);

        $constancias = $this->constancias($anio, $mes);
        $filas = $constancias->map(fn (SolicitudConstancia $constancia) => [
            'constancia' => $constancia,
            'qr' => $this->qr->dataUri($constancia),
        ]);

        $nombre = $mes
            ? sprintf('constancias-%d-%02d.pdf', $anio, $mes)
            : sprintf('constancias-%d.pdf', $anio);

        return Pdf::loadView('reportes.constancias', [
            'filas' => $filas,
            'resumen' => SolicitudConstancia::resumenPorMes($anio),
            'anio' => $anio,
            'mes' => $mes,
            'nombreMes' => $mes ? SolicitudConstancia::MESES[$mes] : null,
        ])->setPaper('letter', 'landscape')->stream($nombre);
    }

    public function csv(Request $request): StreamedResponse
    {
        ['anio' => $anio, 'mes' => $mes] = $this->periodo($request);
        $constancias = $this->constancias($anio, $mes);

        $nombre = $mes
            ? sprintf('constancias-%d-%02d.csv', $anio, $mes)
            : sprintf('constancias-%d.csv', $anio);

        return response()->streamDownload(function () use ($constancias) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, [
                'id_constancias',
                'nombre_empleado',
                'correo',
                'movil',
                'tipo_de_constancia',
                'descripcion',
                'cantidad',
                'fecha_de_solicitud',
                'hora_de_solicitud',
                'estado_solicitud',
                'estado',
            ]);

            foreach ($constancias as $constancia) {
                fputcsv($salida, [
                    $constancia->id_constancias,
                    $constancia->nombre_empleado,
                    $constancia->correo,
                    $constancia->movil,
                    $constancia->tipo_de_constancia,
                    $constancia->descripcion,
                    $constancia->cantidad,
                    $constancia->fecha_de_solicitud?->format('Y-m-d'),
                    $constancia->hora_de_solicitud,
                    $constancia->estado_solicitud ? '1' : '0',
                    $constancia->estado_texto,
                ]);
            }

            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function porMes(Request $request): JsonResponse
    {
        ['anio' => $anio] = $this->periodo($request);
        $meses = SolicitudConstancia::resumenPorMes($anio);

        return response()->json([
            'anio' => $anio,
            'meses' => $meses,
            'totales' => [
                'solicitudes' => array_sum(array_column($meses, 'solicitudes')),
                'cantidad' => array_sum(array_column($meses, 'cantidad')),
                'activas' => array_sum(array_column($meses, 'activas')),
                'inactivas' => array_sum(array_column($meses, 'inactivas')),
            ],
        ]);
    }

    public function grafico(Request $request): JsonResponse
    {
        ['anio' => $anio, 'mes' => $mes] = $this->periodo($request, true);

        $base = SolicitudConstancia::query()->delPeriodo($anio, $mes);
        $activas = (clone $base)->where('estado_solicitud', true)->count();
        $inactivas = (clone $base)->where('estado_solicitud', false)->count();

        return response()->json([
            'anio' => $anio,
            'mes' => $mes,
            'nombre_mes' => SolicitudConstancia::MESES[$mes],
            'activas' => $activas,
            'inactivas' => $inactivas,
            'total' => $activas + $inactivas,
            'labels' => ['Activas', 'Inactivas'],
            'data' => [$activas, $inactivas],
        ]);
    }

    /**
     * @return array{anio: int, mes: ?int}
     */
    private function periodo(Request $request, bool $mesRequerido = false): array
    {
        $datos = $request->validate([
            'anio' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'mes' => [$mesRequerido ? 'required' : 'nullable', 'integer', 'min:1', 'max:12'],
        ], [
            'mes.required' => 'Seleccione el mes.',
            'mes.integer' => 'El mes no es válido.',
            'mes.min' => 'El mes debe estar entre 1 y 12.',
            'mes.max' => 'El mes debe estar entre 1 y 12.',
            'anio.integer' => 'El año no es válido.',
            'anio.min' => 'El año no es válido.',
            'anio.max' => 'El año no es válido.',
        ]);

        return [
            'anio' => (int) ($datos['anio'] ?? now()->year),
            'mes' => isset($datos['mes']) ? (int) $datos['mes'] : null,
        ];
    }

    private function constancias(int $anio, ?int $mes)
    {
        return SolicitudConstancia::query()
            ->delPeriodo($anio, $mes)
            ->orderBy('fecha_de_solicitud')
            ->orderBy('hora_de_solicitud')
            ->get();
    }
}
