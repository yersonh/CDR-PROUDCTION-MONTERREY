<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 110px 40px 60px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 10.5px; line-height: 1.4; }
        p { margin: 0; }

        .header { position: fixed; top: -95px; left: 0; right: 0; }
        .header table { width: 100%; border-collapse: collapse; }
        .header .logo { width: 46px; height: 46px; }
        .header .nombre { font-size: 13px; font-weight: bold; color: #0a0e1c; }
        .header .subnombre { font-size: 9.5px; color: #64748b; }
        .header .fecha { text-align: right; font-size: 9px; color: #64748b; }
        .header hr { border: none; border-top: 2px solid #c8a800; margin-top: 8px; }

        .footer { position: fixed; bottom: -45px; left: 0; right: 0; font-size: 8.5px; color: #94a3b8; text-align: center; }

        .titulo { font-size: 16px; font-weight: bold; color: #0a0e1c; margin: 0 0 2px; }
        .subtitulo { font-size: 10px; color: #64748b; margin-bottom: 14px; }

        .filtros { background: #f1f5f9; border-radius: 6px; padding: 8px 12px; font-size: 9.5px; color: #334155; margin-bottom: 16px; }
        .filtros strong { color: #0a0e1c; }

        .seccion { font-size: 12px; font-weight: bold; color: #0a0e1c; margin: 18px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #cbd5e1; }

        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .kpis td { width: 20%; text-align: center; padding: 8px 4px; }
        .kpis .valor { font-size: 18px; font-weight: bold; color: #14306a; }
        .kpis .etiqueta { font-size: 8.5px; color: #64748b; text-transform: uppercase; }

        table.datos { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        table.datos th { text-align: left; background: #f1f5f9; color: #334155; padding: 5px 8px; text-transform: uppercase; font-size: 8px; }
        table.datos td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
        table.datos .num { text-align: right; }

        .barra-fondo { background: #e2e8f0; border-radius: 3px; height: 7px; width: 100px; display: inline-block; vertical-align: middle; }
        .barra { background: #14306a; border-radius: 3px; height: 7px; display: block; }

        .vacio { color: #94a3b8; font-style: italic; padding: 6px 0; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 50px;"><img src="{{ public_path('branding/escudo.png') }}" class="logo"></td>
                <td>
                    <p class="nombre">Alcaldía de Monterrey Casanare</p>
                    <p class="subnombre">Certificado de Residencia Digital — Reporte gerencial</p>
                </td>
                <td class="fecha">Generado: {{ $generadoEn }}<br>Por: {{ $generadoPor }}</td>
            </tr>
        </table>
        <hr>
    </div>

    <div class="footer">
        Alcaldía de Monterrey Casanare · Certificado de Residencia Digital · Desarrollado por NexGovIA
    </div>

    <p class="titulo">Reporte de gestión de solicitudes</p>
    <p class="subtitulo">Cumplimiento de SLA, productividad y tendencias</p>

    <div class="filtros">
        <strong>Filtros aplicados:</strong>
        Desde: {{ $data['filtros_aplicados']['desde'] ?? 'Sin definir' }} ·
        Hasta: {{ $data['filtros_aplicados']['hasta'] ?? 'Sin definir' }} ·
        Dependencia: {{ $dependenciaNombre ?? 'Todas' }} ·
        Estado: {{ $data['filtros_aplicados']['estado'] ?? 'Todos' }} ·
        Medio: {{ $data['filtros_aplicados']['medio_acreditacion'] ?? 'Todos' }}
    </div>

    <table class="kpis">
        <tr>
            <td><p class="valor">{{ $data['resumen']['total'] }}</p><p class="etiqueta">Solicitudes</p></td>
            <td><p class="valor">{{ $data['resumen']['certificadas'] }}</p><p class="etiqueta">Certificadas</p></td>
            <td><p class="valor">{{ $data['resumen']['pendientes'] }}</p><p class="etiqueta">Pendientes</p></td>
            <td><p class="valor">{{ $data['resumen']['rechazadas'] }}</p><p class="etiqueta">Rechazadas</p></td>
            <td><p class="valor">{{ $data['resumen']['tiempo_promedio_dias'] ?? '—' }}</p><p class="etiqueta">Días promedio</p></td>
        </tr>
    </table>

    <p class="seccion">Cumplimiento de SLA (15 días hábiles)</p>
    <table class="kpis">
        <tr>
            <td><p class="valor">{{ $data['sla']['verde'] }}</p><p class="etiqueta">En verde</p></td>
            <td><p class="valor">{{ $data['sla']['ambar'] }}</p><p class="etiqueta">En ámbar</p></td>
            <td><p class="valor">{{ $data['sla']['rojo'] }}</p><p class="etiqueta">En rojo</p></td>
            <td><p class="valor">{{ $data['sla']['vencidas'] }}</p><p class="etiqueta">Vencidas</p></td>
            <td><p class="valor">{{ $data['sla']['cumplimiento_pct'] !== null ? $data['sla']['cumplimiento_pct'].'%' : '—' }}</p><p class="etiqueta">% a tiempo</p></td>
        </tr>
    </table>

    <p class="seccion">Solicitudes por estado</p>
    @if(collect($data['por_estado'])->sum('total') > 0)
        @php $maxEstado = collect($data['por_estado'])->max('total') ?: 1; @endphp
        <table class="datos">
            @foreach($data['por_estado'] as $e)
                @if($e['total'] > 0)
                <tr>
                    <td>{{ $e['label'] }}</td>
                    <td><span class="barra-fondo"><span class="barra" style="width: {{ round($e['total'] / $maxEstado * 100) }}%"></span></span></td>
                    <td class="num">{{ $e['total'] }}</td>
                </tr>
                @endif
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Por medio de acreditación</p>
    @if(collect($data['por_medio'])->sum('total') > 0)
        @php $maxMedio = collect($data['por_medio'])->max('total') ?: 1; @endphp
        <table class="datos">
            @foreach($data['por_medio'] as $m)
                @if($m['total'] > 0)
                <tr>
                    <td>{{ $m['label'] }}</td>
                    <td><span class="barra-fondo"><span class="barra" style="width: {{ round($m['total'] / $maxMedio * 100) }}%"></span></span></td>
                    <td class="num">{{ $m['total'] }}</td>
                </tr>
                @endif
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Radicados en el tiempo</p>
    @if(count($data['tendencia']))
        <table class="datos">
            <tr><th>Periodo</th><th class="num">Radicados</th></tr>
            @foreach($data['tendencia'] as $t)
                <tr><td>{{ $t['label'] }}</td><td class="num">{{ $t['total'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Top dependencias</p>
    @if(count($data['por_dependencia']))
        <table class="datos">
            <tr><th>Dependencia</th><th class="num">Total</th></tr>
            @foreach(array_slice($data['por_dependencia'], 0, 10) as $d)
                <tr><td>{{ $d['nombre'] }}</td><td class="num">{{ $d['total'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Productividad por funcionario</p>
    @if(count($data['productividad']))
        <table class="datos">
            <tr><th>Funcionario</th><th class="num">Validaciones</th><th class="num">Firmas</th><th class="num">Total</th></tr>
            @foreach($data['productividad'] as $p)
                <tr><td>{{ $p['nombre'] }}</td><td class="num">{{ $p['validaciones'] }}</td><td class="num">{{ $p['firmas'] }}</td><td class="num">{{ $p['total'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Rechazos recientes</p>
    @if(count($data['rechazos_recientes']))
        <table class="datos">
            <tr><th>Radicado</th><th>Solicitante</th><th>Fecha</th><th>Motivo</th></tr>
            @foreach($data['rechazos_recientes'] as $r)
                <tr>
                    <td>{{ $r['radicado'] }}</td>
                    <td>{{ $r['nombre_completo'] }}</td>
                    <td>{{ $r['fecha_radicacion'] }}</td>
                    <td>{{ $r['motivo'] ?? 'Sin motivo registrado' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin rechazos en el rango seleccionado.</p>
    @endif

    <p class="seccion">Integración VUR</p>
    <table class="kpis">
        <tr>
            <td><p class="valor">{{ $data['vur']['recibidos'] }}</p><p class="etiqueta">Recibidos</p></td>
            <td><p class="valor">{{ $data['vur']['radicados'] }}</p><p class="etiqueta">Radicados</p></td>
            <td><p class="valor">{{ $data['vur']['pendientes'] }}</p><p class="etiqueta">Pendientes</p></td>
        </tr>
    </table>

</body>
</html>
