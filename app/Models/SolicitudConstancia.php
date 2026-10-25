<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudConstancia extends Model
{
    public const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    protected $table = 'solicitud_de_constancias';

    protected $primaryKey = 'id_constancias';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'nombre_empleado',
        'correo',
        'movil',
        'tipo_de_constancia',
        'descripcion',
        'cantidad',
        'fecha_de_solicitud',
        'hora_de_solicitud',
        'estado_solicitud',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'fecha_de_solicitud' => 'date',
        'estado_solicitud' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEstadoTextoAttribute(): string
    {
        return $this->estado_solicitud ? 'Activa' : 'Inactiva';
    }

    public function scopeDelPeriodo(Builder $query, int $anio, ?int $mes = null): Builder
    {
        $query->whereYear('fecha_de_solicitud', $anio);

        if ($mes) {
            $query->whereMonth('fecha_de_solicitud', $mes);
        }

        return $query;
    }

    /**
     * Constancias registradas por mes: cantidad de solicitudes,
     * suma del campo cantidad, activas e inactivas.
     *
     * @return array<int, array<string, int|string>>
     */
    public static function resumenPorMes(int $anio): array
    {
        $driver = static::query()->getConnection()->getDriverName();
        $mes = $driver === 'pgsql'
            ? 'EXTRACT(MONTH FROM fecha_de_solicitud)'
            : 'MONTH(fecha_de_solicitud)';
        $activas = $driver === 'pgsql'
            ? 'COALESCE(SUM(CASE WHEN estado_solicitud THEN 1 ELSE 0 END), 0)'
            : 'COALESCE(SUM(estado_solicitud = 1), 0)';
        $inactivas = $driver === 'pgsql'
            ? 'COALESCE(SUM(CASE WHEN NOT estado_solicitud THEN 1 ELSE 0 END), 0)'
            : 'COALESCE(SUM(estado_solicitud = 0), 0)';

        $filas = static::query()
            ->whereYear('fecha_de_solicitud', $anio)
            ->selectRaw($mes.' as mes')
            ->selectRaw('COUNT(*) as solicitudes')
            ->selectRaw('COALESCE(SUM(cantidad), 0) as cantidad')
            ->selectRaw($activas.' as activas')
            ->selectRaw($inactivas.' as inactivas')
            ->groupByRaw($mes)
            ->orderByRaw($mes)
            ->get()
            ->keyBy(fn ($fila) => (int) $fila->mes);

        $meses = [];

        foreach (self::MESES as $numero => $nombre) {
            $fila = $filas->get($numero);

            $meses[] = [
                'mes' => $numero,
                'nombre' => $nombre,
                'solicitudes' => (int) ($fila->solicitudes ?? 0),
                'cantidad' => (int) ($fila->cantidad ?? 0),
                'activas' => (int) ($fila->activas ?? 0),
                'inactivas' => (int) ($fila->inactivas ?? 0),
            ];
        }

        return $meses;
    }
}
