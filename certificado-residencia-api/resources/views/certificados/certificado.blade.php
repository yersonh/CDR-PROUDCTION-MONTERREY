<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 175px 60px 115px 60px; }
        body { font-family: 'DejaVu Serif', serif; color: #000; font-size: 11.5px; line-height: 1.42; }
        p { margin: 0 0 9px; }

        .header { position: fixed; top: -160px; left: 0; right: 0; text-align: center; }
        .header .logo { max-width: 280px; }

        .qr-esquina { position: fixed; top: -125px; right: 0; text-align: right; }
        .qr-esquina img { width: 72px; height: 72px; }

        .footer { position: fixed; bottom: -100px; left: 0; right: 0; font-size: 9px; color: #000; text-align: center; }
        .footer .direccion { text-align: left; line-height: 1.5; margin-bottom: 6px; }
        .footer .pagina { text-align: center; }

        .meta-row { width: 100%; font-size: 9px; color: #000; margin-bottom: 14px; text-align: left; }

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

        /* Firma del Alcalde: más grande, centrada — como en el documento físico. */
        .alcalde-firma { text-align: center; }
        .alcalde-firma .firma-img { max-width: 180px; max-height: 65px; }
        .alcalde-firma .firma-line { display: inline-block; border-top: 1px solid #000; padding-top: 3px; margin-top: 2px; font-size: 11px; width: 230px; text-align: center; }
        .firma-nombre { font-weight: bold; text-transform: uppercase; }
        .firma-tag { font-size: 9px; color: #000; }

        /* Firma de la Secretaría: más pequeña, alineada a la izquierda, debajo. */
        .secretaria-firma { text-align: left; margin-top: 16px; }
        .secretaria-firma .firma-img { max-width: 120px; max-height: 40px; }
        .secretaria-firma .firma-line { border-top: 1px solid #000; padding-top: 2px; margin-top: 1px; font-size: 9.5px; width: 230px; text-align: left; }
        .secretaria-firma .firma-nombre { font-size: 10px; }
        .secretaria-firma .firma-tag { font-size: 8.5px; }

    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $escudo }}" class="logo" alt="Alcaldía de Monterrey, Casanare">
    </div>

    <div class="qr-esquina">
        <img src="{{ $qr }}" alt="QR de verificación">
    </div>

    <div class="footer">
        <div class="direccion">
            Carrera 6 15-72<br>
            Código Postal 855 010<br>
            Pbx (8) 624 9890<br>
            www.monterrey-casanare.gov.co
        </div>
        <div class="pagina">Página 1</div>
    </div>

    <div class="meta-row">No. {{ $certificado->consecutivo }} · Radicado {{ $certificado->solicitud->radicado }} · Código de verificación {{ $certificado->codigo_verificacion }}</div>

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
        <div class="alcalde-firma">
            @if (!empty($firma_img))
                <img src="{{ $firma_img }}" alt="Firma" class="firma-img"><br>
            @endif
            <div class="firma-line">
                <span class="firma-nombre">{{ $certificado->firmadoPor->name ?? 'Alcalde Municipal' }}</span><br>
                Alcalde Municipal<br>
                <span class="firma-tag">Despacho del Alcalde</span>
            </div>
        </div>

        @if ($proyecto_nombre)
            <div class="secretaria-firma">
                @if (!empty($proyecto_img))
                    <img src="{{ $proyecto_img }}" alt="Firma" class="firma-img"><br>
                @endif
                <div class="firma-line">
                    <span class="firma-nombre">{{ mb_strtoupper($proyecto_nombre, 'UTF-8') }}</span><br>
                    <span class="firma-tag">Secretaría Ejecutiva del Despacho del Alcalde</span>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
