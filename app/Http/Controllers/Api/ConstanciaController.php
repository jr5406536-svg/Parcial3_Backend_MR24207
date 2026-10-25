<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitarConstanciaRequest;
use App\Http\Requests\StoreConstanciaRequest;
use App\Http\Requests\UpdateConstanciaRequest;
use App\Http\Resources\ConstanciaResource;
use App\Mail\ConstanciaListaMail;
use App\Models\SolicitudConstancia;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class ConstanciaController extends Controller
{
    public function __construct(private readonly QrService $qr)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'estado' => ['nullable', 'in:activa,inactiva,1,0,true,false'],
            'mes' => ['nullable', 'integer', 'min:1', 'max:12'],
            'anio' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'nombre' => ['nullable', 'string', 'max:150'],
            'tipo' => ['nullable', 'string', 'max:100'],
        ], [
            'estado.in' => 'El estado debe ser activa o inactiva.',
            'mes.integer' => 'El mes no es válido.',
            'mes.min' => 'El mes debe estar entre 1 y 12.',
            'mes.max' => 'El mes debe estar entre 1 y 12.',
        ]);

        $consulta = SolicitudConstancia::query()
            ->with('user')
            ->orderByDesc('fecha_de_solicitud')
            ->orderByDesc('hora_de_solicitud')
            ->orderByDesc('id_constancias');

        if ($request->filled('estado')) {
            $estado = strtolower((string) $request->input('estado'));
            $activa = in_array($estado, ['activa', '1', 'true'], true);
            $consulta->where('estado_solicitud', $activa);
        }

        if ($request->filled('mes')) {
            $consulta->whereMonth('fecha_de_solicitud', $request->integer('mes'));
        }

        if ($request->filled('anio')) {
            $consulta->whereYear('fecha_de_solicitud', $request->integer('anio'));
        }

        if ($request->filled('nombre')) {
            $consulta->where('nombre_empleado', 'ilike', '%'.$this->textoFiltro($request->input('nombre')).'%');
        }

        if ($request->filled('tipo')) {
            $consulta->where('tipo_de_constancia', 'ilike', '%'.$this->textoFiltro($request->input('tipo')).'%');
        }

        return ConstanciaResource::collection(
            $consulta->paginate(5)->withQueryString()
        );
    }

    public function store(StoreConstanciaRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $constancia = SolicitudConstancia::create([
            ...$datos,
            'fecha_de_solicitud' => now()->toDateString(),
            'hora_de_solicitud' => now()->format('H:i:s'),
        ]);

        return (new ConstanciaResource($constancia->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SolicitudConstancia $constancia): ConstanciaResource
    {
        return new ConstanciaResource($constancia->load('user'));
    }

    public function solicitar(SolicitarConstanciaRequest $request): JsonResponse
    {
        $usuario = $request->user();

        $constancia = SolicitudConstancia::create([
            ...$request->validated(),
            'nombre_empleado' => $usuario->name,
            'correo' => $usuario->email,
            'user_id' => $usuario->id,
            'estado_solicitud' => true,
            'fecha_de_solicitud' => now()->toDateString(),
            'hora_de_solicitud' => now()->format('H:i:s'),
        ]);

        return (new ConstanciaResource($constancia->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function mias(Request $request): AnonymousResourceCollection
    {
        $consulta = SolicitudConstancia::query()
            ->with('user')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('fecha_de_solicitud')
            ->orderByDesc('hora_de_solicitud')
            ->orderByDesc('id_constancias');

        return ConstanciaResource::collection(
            $consulta->paginate(5)->withQueryString()
        );
    }

    public function mia(Request $request, SolicitudConstancia $constancia): ConstanciaResource
    {
        $this->exigirDueno($request, $constancia);

        return new ConstanciaResource($constancia->load('user'));
    }

    public function qrPropio(Request $request, SolicitudConstancia $constancia): Response
    {
        $this->exigirDueno($request, $constancia);

        return $this->qr($constancia);
    }

    public function update(UpdateConstanciaRequest $request, SolicitudConstancia $constancia): ConstanciaResource
    {
        $datos = $request->validated();
        $pasaAInactiva = $constancia->estado_solicitud && ! $datos['estado_solicitud'];

        $constancia->update($datos);

        $destino = null;
        $aviso = null;
        if ($pasaAInactiva) {
            $resultado = $this->enviarAlSolicitante($constancia->refresh());
            $destino = $resultado['correo'];
            $aviso = $resultado['message'];
        }

        return (new ConstanciaResource($constancia->refresh()->load('user')))
            ->additional($aviso ? [
                'correo_enviado' => $destino,
                'message' => $aviso,
            ] : []);
    }

    public function destroy(SolicitudConstancia $constancia): JsonResponse
    {
        $constancia->delete();

        return response()->json([
            'message' => 'Constancia eliminada.',
        ]);
    }

    public function enviarCorreo(SolicitudConstancia $constancia): JsonResponse
    {
        $estabaActiva = (bool) $constancia->estado_solicitud;

        if ($estabaActiva) {
            $constancia->update(['estado_solicitud' => false]);
        }

        $resultado = $this->enviarAlSolicitante($constancia->refresh(), $estabaActiva);

        return response()->json([
            'message' => $resultado['message'],
            'correo' => $resultado['correo'],
        ]);
    }

    public function qr(SolicitudConstancia $constancia): Response
    {
        return response($this->qr->png($constancia), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="constancia-'.$constancia->id_constancias.'.png"',
        ]);
    }

    private function correoDelSolicitante(SolicitudConstancia $constancia): string
    {
        $constancia->loadMissing('user');

        return $constancia->user?->email ?: $constancia->correo;
    }

    /**
     * @return array{correo: ?string, message: string}
     */
    private function enviarAlSolicitante(SolicitudConstancia $constancia, bool $revertirSiFalla = true): array
    {
        $destino = $this->correoReal($constancia);

        if (! $destino) {
            return [
                'correo' => null,
                'message' => 'Quedó inactiva. El correo no es real, así que no se envió el aviso.',
            ];
        }

        try {
            Mail::to($destino)->send(new ConstanciaListaMail($constancia));
        } catch (\Throwable $error) {
            if ($revertirSiFalla && ! $constancia->estado_solicitud) {
                $constancia->update(['estado_solicitud' => true]);
            }

            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                'message' => 'No se pudo enviar el correo a '.$destino.'. La constancia sigue activa.',
            ], 502));
        }

        return [
            'correo' => $destino,
            'message' => 'Constancia enviada a '.$destino.'. Quedó inactiva.',
        ];
    }

    private function correoReal(SolicitudConstancia $constancia): ?string
    {
        $destino = strtolower(trim($this->correoDelSolicitante($constancia)));

        if (! filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $dominio = substr(strrchr($destino, '@'), 1);
        $falsos = ['correo.com', 'example.com', 'example.org', 'example.net', 'test.com', 'localhost'];

        if ($dominio === '' || ! str_contains($dominio, '.') || in_array($dominio, $falsos, true)) {
            return null;
        }

        return $destino;
    }

    private function exigirDueno(Request $request, SolicitudConstancia $constancia): void
    {
        if ($constancia->user_id !== $request->user()->id) {
            abort(response()->json([
                'message' => 'No se encontró la constancia.',
            ], 404));
        }
    }

    private function textoFiltro(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($valor));
    }
}
