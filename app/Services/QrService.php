<?php

namespace App\Services;

use App\Models\SolicitudConstancia;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrService
{
    public function contenido(SolicitudConstancia $constancia): string
    {
        return implode("\n", [
            'Solicitud de constancia',
            'ID: '.$constancia->id_constancias,
            'Empleado: '.$constancia->nombre_empleado,
            'Tipo: '.$constancia->tipo_de_constancia,
            'Fecha: '.$constancia->fecha_de_solicitud?->format('Y-m-d'),
            'Estado: '.$constancia->estado_texto,
        ]);
    }

    public function png(SolicitudConstancia $constancia): string
    {
        return $this->pngTexto($this->contenido($constancia));
    }

    public function pngTexto(string $texto): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'scale' => 6,
        ]);

        return (new QRCode($options))->render($texto);
    }

    public function dataUri(SolicitudConstancia $constancia): string
    {
        return 'data:image/png;base64,'.base64_encode($this->png($constancia));
    }
}
