<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 145px 60px 115px 60px; }
        body { font-family: 'DejaVu Serif', serif; color: #000; font-size: 11.5px; line-height: 1.42; }
        p { margin: 0 0 9px; }

        .header { position: fixed; top: -128px; left: 0; right: 0; text-align: center; }
        .header .escudo { height: 55px; }
        .header .entidad { font-size: 15px; font-weight: bold; color: #000; margin-top: 4px; letter-spacing: 0.5px; }
        .header .nit { font-size: 10px; color: #000; margin-top: 2px; }
        .header hr { border: none; border-top: 1.5px solid #000; margin: 6px 0 0; }

        .footer { position: fixed; bottom: -100px; left: 0; right: 0; font-size: 9px; color: #000; text-align: center; }
        .footer hr { border: none; border-top: 1px solid #000; margin-bottom: 6px; }
        .footer .direccion { text-align: left; line-height: 1.5; margin-bottom: 6px; }
        .footer .pagina { text-align: center; }

        .meta-row { width: 100%; font-size: 9px; color: #000; margin-bottom: 14px; text-align: right; }

        .titulo { text-align: center; font-size: 13px; font-weight: bold; margin: 0 0 12px; text-transform: uppercase; }

        .cuerpo { text-align: justify; }
        .cuerpo strong { font-weight: bold; }
        .certifica { text-align: center; font-weight: bold; font-size: 13px; margin: 12px 0; }
        .cierre p { text-align: center; margin: 0 0 7px; }

        .requisitos { width: 100%; margin: 8px 0 12px; font-size: 11.5px; }
        .requisitos td { padding: 2px 0; }
        .requisitos td.req-label { width: 75%; }
        .requisitos td.req-valor { text-align: right; font-weight: bold; }

        .firma-wrap { width: 100%; margin-top: 16px; }
        .firma-box { display: inline-block; width: 55%; vertical-align: bottom; }
        .firma-img { max-width: 150px; max-height: 55px; margin-bottom: 2px; }
        .firma-line { border-top: 1px solid #000; padding-top: 3px; font-size: 10.5px; width: 75%; }
        .firma-nombre { font-weight: bold; text-transform: uppercase; }
        .firma-tag { font-size: 9px; color: #000; }
        .proyecto-box { margin-top: 12px; }
        .qr-box { display: inline-block; width: 40%; text-align: right; vertical-align: bottom; }
        .qr-box img { width: 85px; height: 85px; }

    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $escudo }}" class="escudo" alt="Escudo">
        <div class="entidad">ALCALDÍA DE MONTERREY CASANARE</div>
        <div class="nit">NIT. 891857 824-3</div>
        <hr>
    </div>

    <div class="footer">
        <hr>
        <div class="direccion">
            Carrera 6 15-72<br>
            Código Postal 855 010<br>
            Pbx (8) 624 9890<br>
            www.monterrey-casanare.gov.co
        </div>
        <div class="pagina">Página 1</div>
    </div>

    <div class="meta-row">No. {{ $certificado->consecutivo }} · Radicado {{ $certificado->solicitud->radicado }}</div>

    <div class="titulo">EL ALCALDE MUNICIPAL DE MONTERREY CASANARE</div>

    <div class="cuerpo">
        <p>
            En virtud de lo previsto en el artículo 315 de la Constitución Política, el articulo 29 literal F numeral
            6 de la Ley 1551 de 2012, los artículos 2.3.2.3.2 del Decreto 1158 de 2019 por medio del cual se
            adiciona el capítulo 3 al título 2 de la parte 3 del libro 2 del Decreto 1066 de 2015, los alcaldes
            municipales son las únicas autoridades competentes para expedir los certificados de residencia, en las
            áreas de influencia de los proyectos de exploración y explotación petrolera y minera, que aspiren
            acceder a labores como mano de obra no calificada. Los alcaldes expedirán dichos certificados con
            base en: censo electoral, sistema de identificación de potenciales beneficiarios de programas sociales
            Sisben y libros de afiliados a juntas de acción comunal, debidamente registrados ante el ente de
            control y vigilancia, siempre y cuando el ciudadano lleve más de un año inscrito en los mismos.
        </p>

        <p class="certifica">CERTIFICA:</p>

        <p>
            Que <strong>{{ mb_strtoupper($s->nombre_completo, 'UTF-8') }}</strong>, identificado(a) con {{ $tipo_documento_label }}
            No. <strong>{{ $s->numero_identificacion }}</strong>, con dirección de residencia
            <strong>{{ $s->direccion }}</strong>, sector {{ $s->barrio_vereda_sector }}.
        </p>

        <p>Si es residente en el Municipio de <strong>Monterrey - Casanare</strong>.</p>

        <p>Por cumplir con el siguiente requisito:</p>

        <table class="requisitos">
            <tr>
                <td class="req-label">{{ mb_strtoupper($s->medio_acreditacion->label(), 'UTF-8') }}:</td>
                <td class="req-valor">Cumple</td>
            </tr>
        </table>

        <div class="cierre">
            <p>La presente certificación se expide a solicitud escrita del interesado(a).</p>
            <p>
                Dada en Monterrey - Casanare, hoy {{ $dia_letras }}
                ({{ $certificado->fecha_expedicion->format('d') }}) de {{ $meses[(int) $certificado->fecha_expedicion->format('n')] }}
                de {{ $certificado->fecha_expedicion->format('Y') }}.
            </p>
            <p>
                Tendrá vigencia de {{ $meses_vigencia_letras }} ({{ $meses_vigencia }}) meses, contados a partir de la
                expedición.
            </p>
        </div>
    </div>

    <div class="firma-wrap">
        <div class="firma-box">
            @if (!empty($firma_img))
                <img src="{{ $firma_img }}" alt="Firma" class="firma-img"><br>
            @endif
            <div class="firma-line">
                <span class="firma-nombre">{{ $certificado->firmadoPor->name ?? 'Alcalde Municipal' }}</span><br>
                Alcalde Municipal<br>
                <span class="firma-tag">Despacho del Alcalde</span>
            </div>

            @if ($proyecto_nombre)
                <div class="proyecto-box">
                    @if (!empty($proyecto_img))
                        <img src="{{ $proyecto_img }}" alt="Firma" class="firma-img"><br>
                    @endif
                    <div class="firma-line">
                        {{ $proyecto_nombre }}<br>
                        <span class="firma-tag">Proyectó · Secretaría Ejecutiva del Despacho del Alcalde</span>
                    </div>
                </div>
            @endif
        </div>
        <div class="qr-box">
            <img src="{{ $qr }}" alt="QR de verificación">
        </div>
    </div>
</body>
</html>
