<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 70px 70px 60px 70px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; font-size: 12px; line-height: 1.7; }
        .footer { position: fixed; bottom: -45px; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; }
        p { margin: 0 0 14px; }
        .fecha { margin-bottom: 20px; }
        .destinatario { margin-bottom: 20px; }
        .asunto { margin-bottom: 20px; }
        .cuerpo { text-align: justify; }
        .firma { margin-top: 60px; }
        .firma .espacio { height: 50px; }
        .datos-firmante { margin-top: 4px; }
        .datos-firmante div { margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="footer">
        Solicitud generada por el ciudadano a través del portal público de la Alcaldía de Monterrey (Casanare) · Ref. {{ $referencia }} · {{ $fecha }}
    </div>

    <p class="fecha">Monterrey, {{ $fechaLarga }}</p>

    <p class="destinatario">
        Señores<br>
        ALCALDÍA DE MONTERREY CASANARE
    </p>

    <p class="asunto"><strong>ASUNTO:</strong> Solicitud carta de residencia</p>

    <p>Cordial saludo,</p>

    <div class="cuerpo">
        <p>
            Yo, <strong>{{ strtoupper($s->nombre_completo) }}</strong>, identificado(a) con
            {{ $s->tipo_documento ?? 'documento de identidad' }} No. <strong>{{ $s->numero_identificacion }}</strong>,
            residente en {{ $s->direccion }}, sector {{ $s->barrio_vereda_sector }}, solicito formalmente me sea
            asignada y generada mi carta de residencia de esta localidad
            @if ($s->motivo)
                para {{ $s->motivo }}.
            @else
                para los fines que estime pertinentes.
            @endif
        </p>

        <p>
            Acredito mi residencia en cumplimiento del Decreto 1158 de 2019 (que adiciona el Decreto 1066 de
            2015, artículo 2.3.2.3.1), por medio de: <strong>{{ $s->medio_acreditacion->label() }}</strong>.
        </p>

        <p>Agradezco la gestión que se sirvan prestar a la presente.</p>
    </div>

    <p>Atentamente,</p>

    <div class="firma">
        <div class="espacio"></div>
        <div class="datos-firmante">
            <div><strong>{{ strtoupper($s->nombre_completo) }}</strong></div>
            <div>{{ $s->tipo_documento ?? 'CC' }} {{ $s->numero_identificacion }}</div>
            <div>CEL {{ $s->celular }}</div>
            <div>EMAIL {{ $s->correo }}</div>
            <div>DIRECCIÓN {{ $s->direccion }} {{ $s->barrio_vereda_sector }}</div>
        </div>
    </div>
</body>
</html>
