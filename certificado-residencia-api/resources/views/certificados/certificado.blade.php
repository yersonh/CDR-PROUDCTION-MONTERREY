<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 160px 60px 90px 60px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 12px; line-height: 1.6; }
        .header { position: fixed; top: -140px; left: 0; right: 0; text-align: center; }
        .header .escudo { height: 70px; }
        .header .entidad { font-size: 13px; font-weight: bold; color: #1b3a6e; margin-top: 4px; }
        .header .sub { font-size: 10px; color: #64748b; }
        .header hr { border: none; border-top: 2px solid #c8a800; margin: 6px 0 0; }
        .footer { position: fixed; bottom: -70px; left: 0; right: 0; font-size: 9px; color: #64748b; text-align: center; }
        .footer hr { border: none; border-top: 1px solid #cbd5e1; margin-bottom: 4px; }
        .titulo { text-align: center; font-size: 18px; font-weight: bold; color: #1b3a6e; letter-spacing: 1px; margin: 10px 0 2px; }
        .consecutivo { text-align: center; font-size: 12px; color: #64748b; margin-bottom: 22px; }
        .cuerpo { text-align: justify; }
        .cuerpo strong { color: #14306a; }
        .datos { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .datos td { padding: 5px 8px; border: 1px solid #e2e8f0; font-size: 11px; }
        .datos td.k { background: #f1f5f9; color: #64748b; width: 32%; font-weight: bold; }
        .firma-wrap { width: 100%; margin-top: 34px; }
        .firma-box { width: 46%; }
        .firma-line { border-top: 1px solid #1e293b; padding-top: 4px; font-size: 11px; }
        .firma-nombre { font-weight: bold; color: #1b3a6e; }
        .firma-tag { font-size: 9px; color: #16a34a; }
        .qr-box { width: 46%; text-align: right; }
        .qr-box img { width: 110px; height: 110px; }
        .verif { margin-top: 20px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 9px; color: #475569; }
        .verif .cod { font-weight: bold; color: #1b3a6e; letter-spacing: 1px; }
        .hash { word-break: break-all; font-family: DejaVu Sans Mono, monospace; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $escudo }}" class="escudo" alt="Escudo">
        <div class="entidad">ALCALDÍA MUNICIPAL DE MONTERREY · CASANARE</div>
        <div class="sub">Despacho del Alcalde · Certificado de Residencia Digital</div>
        <hr>
    </div>

    <div class="footer">
        <hr>
        Documento generado electrónicamente conforme al Decreto 1158 de 2019 y los principios de Gobierno Digital.<br>
        Verifique su autenticidad en {{ $verificacion_url }} · Desarrollado por NexGovIA
    </div>

    <div class="titulo">{{ strtoupper($certificado->solicitud->tipo_certificado->label()) }}</div>
    <div class="consecutivo">No. {{ $certificado->consecutivo }} · Radicado {{ $certificado->solicitud->radicado }}</div>

    <div class="cuerpo">
        <p>
            La <strong>Alcaldía Municipal de Monterrey (Casanare)</strong>, en ejercicio de las facultades
            conferidas por el Decreto 1158 de 2019, que adicionó el Decreto 1066 de 2015 (Artículo 2.3.2.3.1),
        </p>
        <p style="text-align:center; font-weight:bold; font-size:14px; color:#1b3a6e; margin:14px 0;">CERTIFICA:</p>
        <p>
            Que el(la) señor(a) <strong>{{ $s->nombre_completo }}</strong>, identificado(a) con
            {{ $s->tipo_documento ?? 'documento' }} No. <strong>{{ $s->numero_identificacion }}</strong>,
            reside en <strong>{{ $s->direccion }}</strong>, sector <strong>{{ $s->barrio_vereda_sector }}</strong>,
            del municipio de Monterrey, departamento de Casanare.
        </p>

        <table class="datos">
            <tr><td class="k">Medio de acreditación</td><td>{{ $s->medio_acreditacion->label() }}</td></tr>
            <tr><td class="k">Fecha de expedición</td><td>{{ $certificado->fecha_expedicion->format('d/m/Y') }}</td></tr>
            <tr><td class="k">Vigencia hasta</td><td>{{ $certificado->vigencia_hasta->format('d/m/Y') }}</td></tr>
        </table>

        <p>
            El presente certificado se expide a solicitud del interesado para los fines que estime convenientes,
            a los {{ $certificado->fecha_expedicion->format('d') }} días del mes de
            {{ $meses[(int) $certificado->fecha_expedicion->format('n')] }} de {{ $certificado->fecha_expedicion->format('Y') }}.
        </p>
    </div>

    <table class="firma-wrap">
        <tr>
            <td class="firma-box" valign="bottom">
                @if (!empty($firma_img))
                    <img src="{{ $firma_img }}" alt="Firma" style="max-height:55px; margin-bottom:2px;">
                @endif
                <div class="firma-line">
                    <span class="firma-nombre">{{ $certificado->firmadoPor->name ?? 'Alcalde Municipal' }}</span><br>
                    Alcalde Municipal de Monterrey, Casanare<br>
                    <span class="firma-tag">✓ Firmado electrónicamente</span>
                </div>
            </td>
            <td></td>
            <td class="qr-box" valign="bottom">
                <img src="{{ $qr }}" alt="QR de verificación">
            </td>
        </tr>
    </table>

    <div class="verif">
        <strong>Autenticidad:</strong> escanee el código QR o verifique con el código
        <span class="cod">{{ $certificado->codigo_verificacion }}</span> en {{ $verificacion_url }}.<br>
        <strong>Integridad:</strong> el hash SHA-256 de este documento se encuentra registrado y puede
        verificarse en el portal de consulta pública.
    </div>
</body>
</html>
