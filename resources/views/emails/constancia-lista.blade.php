<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tu constancia está lista</title>
</head>
<body style="margin:0; padding:24px 12px; background:#e8eef5; font-family:Arial, Helvetica, sans-serif; color:#3d4a5c;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; border-collapse:separate; background:#ffffff; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td align="center" style="background:#1e3a5f; padding:28px 24px 22px;">
                            <div style="color:#ffffff; font-size:26px; font-weight:700; line-height:1.3;">Tu constancia está lista</div>
                            <div style="color:#d5deea; font-size:16px; margin-top:8px;">Sistema de Gestión de Constancias</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 8px; font-size:15px; line-height:1.6;">
                            <p style="margin:0 0 16px;">Estimado/a <strong style="color:#1a1a1a;">{{ $constancia->nombre_empleado }}</strong>:</p>
                            <p style="margin:0 0 18px;">Te informamos que tu constancia ya está preparada y lista para su entrega.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate; background:#f4f7fb; border:1px solid #e4ebf3; border-radius:10px;">
                                <tr>
                                    <td style="padding:18px 20px; font-size:15px; line-height:1.7; color:#2c3a4a;">
                                        <div style="margin-bottom:12px;"><strong>Tipo de constancia:</strong> {{ $constancia->tipo_de_constancia }}</div>
                                        <div style="margin-bottom:12px;"><strong>Cantidad:</strong> {{ $constancia->cantidad }}</div>
                                        <div><strong>Fecha de solicitud:</strong> {{ $constancia->fecha_de_solicitud?->format('d/m/Y') }}</div>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:20px 0 16px;">Por favor, comunícate con el área responsable para coordinar la entrega de tu documento.</p>
                            <p style="margin:0 0 4px;">Saludos cordiales,</p>
                            <p style="margin:0; color:#1a1a1a;"><strong>Administración</strong><br>Sistema de Gestión de Constancias</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background:#f7f9fb; color:#8b97a6; font-size:13px; padding:16px 24px 18px;">
                            Este mensaje fue generado automáticamente. No es necesario responder a este correo.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
