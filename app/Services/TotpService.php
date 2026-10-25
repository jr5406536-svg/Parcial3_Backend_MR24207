<?php

namespace App\Services;

use InvalidArgumentException;

class TotpService
{
    private const ALFABETO = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function secreto(): string
    {
        return $this->base32(random_bytes(20));
    }

    public function url(string $email, string $secreto): string
    {
        $etiqueta = rawurlencode('Constancias:'.$email);

        return 'otpauth://totp/'.$etiqueta
            .'?secret='.$secreto
            .'&issuer=Constancias&digits=6&period=30';
    }

    public function verificar(string $secreto, string $codigo, int $ventana = 1): bool
    {
        if (! preg_match('/^\d{6}$/', $codigo)) {
            return false;
        }

        $ahora = intdiv(time(), 30);

        for ($desfase = -$ventana; $desfase <= $ventana; $desfase++) {
            if (hash_equals($this->codigoEn($secreto, $ahora + $desfase), $codigo)) {
                return true;
            }
        }

        return false;
    }

    public function codigoEn(string $secreto, int $contador): string
    {
        $clave = $this->base32Decode($secreto);
        $alto = ($contador >> 32) & 0xFFFFFFFF;
        $bajo = $contador & 0xFFFFFFFF;
        $hash = hash_hmac('sha1', pack('N2', $alto, $bajo), $clave, true);
        $desplazamiento = ord($hash[19]) & 0x0F;
        $valor = unpack('N', substr($hash, $desplazamiento, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($valor % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32(string $bytes): string
    {
        $bits = '';
        $longitud = strlen($bytes);

        for ($i = 0; $i < $longitud; $i++) {
            $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
        }

        $salida = '';

        foreach (str_split($bits, 5) as $grupo) {
            if (strlen($grupo) < 5) {
                $grupo = str_pad($grupo, 5, '0', STR_PAD_RIGHT);
            }
            $salida .= self::ALFABETO[bindec($grupo)];
        }

        return $salida;
    }

    private function base32Decode(string $secreto): string
    {
        $secreto = strtoupper((string) preg_replace('/[^A-Z2-7]/', '', $secreto));
        $bits = '';
        $longitud = strlen($secreto);

        for ($i = 0; $i < $longitud; $i++) {
            $posicion = strpos(self::ALFABETO, $secreto[$i]);
            if ($posicion === false) {
                throw new InvalidArgumentException('El secreto del código no es válido.');
            }
            $bits .= str_pad(decbin($posicion), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';

        foreach (str_split($bits, 8) as $grupo) {
            if (strlen($grupo) < 8) {
                break;
            }
            $bytes .= chr(bindec($grupo));
        }

        return $bytes;
    }
}
