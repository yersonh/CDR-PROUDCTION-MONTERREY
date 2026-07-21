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
                    <p class="subnombre">Ventanilla Única de Registro (VUR) — Reporte gerencial</p>
                </td>
                <td class="fecha">Generado: {{ $generadoEn }}<br>Por: {{ $generadoPor }}</td>
            </tr>
        </table>
        <hr>
    </div>

    <div class="footer">
        Alcaldía de Monterrey Casanare · Sistema de Radicación de Correspondencia · Desarrollado por NexGovIA
    </div>

    <p class="titulo">Reporte de gestión de correspondencia (VUR)</p>
    <p class="subtitulo">Todos los tipos de trámite radicados — no solo Certificado de Residencia</p>

    <div class="filtros">
        <strong>Rango:</strong> {{ $data['filtros']['fecha_desde'] ?? '—' }} al {{ $data['filtros']['fecha_hasta'] ?? '—' }}
    </div>

    <table class="kpis">
        <tr>
            <td><p class="valor">{{ $data['kpis']['total'] }}</p><p class="etiqueta">Radicados</p></td>
            <td><p class="valor">{{ $data['kpis']['vencidos'] }}</p><p class="etiqueta">Vencidos</p></td>
            <td><p class="valor">{{ $data['kpis']['radicados_respondidos'] }}</p><p class="etiqueta">Respondidos</p></td>
            <td><p class="valor">{{ $data['kpis']['promedio_dias_respuesta'] ?? '—' }}</p><p class="etiqueta">Días promedio</p></td>
        </tr>
    </table>

    <p class="seccion">Cumplimiento de SLA</p>
    <table class="kpis">
        <tr>
            <td><p class="valor">{{ $data['sla']['respondidos_a_tiempo'] }}</p><p class="etiqueta">A tiempo</p></td>
            <td><p class="valor">{{ $data['sla']['respondidos_fuera_plazo'] }}</p><p class="etiqueta">Fuera de plazo</p></td>
            <td><p class="valor">{{ $data['sla']['pendientes_vencidos'] }}</p><p class="etiqueta">Pendientes vencidos</p></td>
            <td><p class="valor">{{ $data['sla']['pendientes_en_plazo'] }}</p><p class="etiqueta">Pendientes en plazo</p></td>
            <td><p class="valor">{{ $data['sla']['cumplimiento_pct'] !== null ? $data['sla']['cumplimiento_pct'].'%' : '—' }}</p><p class="etiqueta">% a tiempo</p></td>
        </tr>
    </table>

    <p class="seccion">Radicados por estado</p>
    @if(count($data['por_estado']))
        @php $maxEstado = collect($data['por_estado'])->max('total') ?: 1; @endphp
        <table class="datos">
            @foreach($data['por_estado'] as $e)
                <tr>
                    <td>{{ $e['descripcion'] }}</td>
                    <td><span class="barra-fondo"><span class="barra" style="width: {{ round($e['total'] / $maxEstado * 100) }}%; background: {{ $e['color_hex'] ?? '#14306a' }}"></span></span></td>
                    <td class="num">{{ $e['total'] }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Por tipo de correspondencia</p>
    @if(count($data['por_tipo']))
        <table class="datos">
            <tr><th>Tipo</th><th class="num">Total</th><th class="num">Vencidos</th><th class="num">% a tiempo</th></tr>
            @foreach(collect($data['por_tipo'])->sortByDesc('total')->take(15) as $t)
                <tr>
                    <td>{{ $t['descripcion'] }}</td>
                    <td class="num">{{ $t['total'] }}</td>
                    <td class="num">{{ $t['vencidos'] }}</td>
                    <td class="num">{{ $t['cumplimiento_pct'] }}%</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Radicados en el tiempo</p>
    @if(count($data['serie_tiempo']))
        <table class="datos">
            <tr><th>Fecha</th><th class="num">Radicados</th></tr>
            @foreach($data['serie_tiempo'] as $s)
                <tr><td>{{ $s['fecha'] }}</td><td class="num">{{ $s['total'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Por dependencia</p>
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

    <p class="seccion">Por operador</p>
    @if(count($data['por_operador']))
        <table class="datos">
            <tr><th>Operador</th><th class="num">Total</th></tr>
            @foreach($data['por_operador'] as $o)
                <tr><td>{{ $o['nombre'] }}</td><td class="num">{{ $o['total'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Top funcionarios destino</p>
    @if(count($data['por_funcionario']))
        <table class="datos">
            <tr><th>Funcionario</th><th class="num">Total</th></tr>
            @foreach($data['por_funcionario'] as $f)
                <tr><td>{{ $f['nombre'] }}</td><td class="num">{{ $f['total'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

    <p class="seccion">Por medio de ingreso</p>
    @if(count($data['por_medio_ingreso']))
        <table class="datos">
            <tr><th>Medio</th><th class="num">Total</th></tr>
            @foreach($data['por_medio_ingreso'] as $m)
                <tr><td>{{ $m['descripcion'] }}</td><td class="num">{{ $m['total'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="vacio">Sin datos para los filtros seleccionados.</p>
    @endif

</body>
</html>
