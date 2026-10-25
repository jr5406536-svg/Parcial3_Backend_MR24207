<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SolicitudConstancia;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $administrador = Role::create(['nombre' => 'administrador']);
        $usuario = Role::create(['nombre' => 'usuario']);
        $totp = app(TotpService::class);

        User::create([
            'name' => 'Administrador',
            'email' => 'admin@constancias.com',
            'password' => 'admin123',
            'password_temporal' => true,
            'totp_secret' => $totp->secreto(),
            'totp_confirmado' => false,
            'role_id' => $administrador->id,
        ]);

        User::create([
            'name' => 'Usuario',
            'email' => 'usuario@constancias.com',
            'password' => 'usuario123',
            'password_temporal' => true,
            'totp_secret' => $totp->secreto(),
            'totp_confirmado' => false,
            'role_id' => $usuario->id,
        ]);

        $ejemplos = [
            ['María López', 'maria.lopez@correo.com', '88881234', 'Laboral', 'Constancia laboral para trámite bancario.', 1, '2026-08-04', '08:15:00', true],
            ['Carlos Ramírez', 'carlos.ramirez@correo.com', '87772345', 'Salarial', 'Constancia salarial del último trimestre.', 2, '2026-08-18', '10:40:00', false],
            ['Ana Gutiérrez', 'ana.gutierrez@correo.com', '86663456', 'De trabajo', 'Constancia de trabajo para la universidad.', 1, '2026-09-02', '09:05:00', true],
            ['José Martínez', 'jose.martinez@correo.com', '85554567', 'Laboral', 'Constancia laboral con cargo y fecha de ingreso.', 1, '2026-09-15', '14:20:00', true],
            ['Lucía Herrera', 'lucia.herrera@correo.com', '84445678', 'De ingresos', 'Constancia de ingresos para alquiler de vivienda.', 3, '2026-09-28', '16:45:00', false],
            ['Pedro Castillo', 'pedro.castillo@correo.com', '83336789', 'Laboral', 'Constancia laboral solicitada por el empleado.', 1, '2026-10-03', '08:50:00', true],
            ['Sofía Delgado', 'sofia.delgado@correo.com', '82227890', 'Salarial', 'Constancia salarial para crédito personal.', 2, '2026-10-07', '11:10:00', true],
            ['Andrés Morales', 'andres.morales@correo.com', '81118901', 'De trabajo', 'Constancia de trabajo para visa.', 1, '2026-10-11', '13:25:00', false],
            ['Elena Vargas', 'elena.vargas@correo.com', '80009012', 'Laboral', 'Constancia laboral del mes en curso.', 1, '2026-10-16', '09:35:00', true],
            ['Diego Navarro', 'diego.navarro@correo.com', '89990123', 'Salarial', 'Constancia salarial con detalle de devengado.', 2, '2026-10-20', '15:55:00', true],
            ['Valeria Cruz', 'valeria.cruz@correo.com', '87881230', 'De antigüedad', 'Constancia de antigüedad laboral.', 1, '2026-10-22', '17:05:00', false],
        ];

        foreach ($ejemplos as $fila) {
            SolicitudConstancia::create([
                'nombre_empleado' => $fila[0],
                'correo' => $fila[1],
                'movil' => $fila[2],
                'tipo_de_constancia' => $fila[3],
                'descripcion' => $fila[4],
                'cantidad' => $fila[5],
                'fecha_de_solicitud' => $fila[6],
                'hora_de_solicitud' => $fila[7],
                'estado_solicitud' => $fila[8],
            ]);
        }
    }
}
