<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoSolicitud;
use App\Enums\MedioAcreditacion;
use App\Enums\ResultadoValidacion;
use App\Http\Controllers\Controller;
use App\Models\Certificado;
use App\Models\RecibidoVur;
use App\Models\Solicitud;
use App\Models\User;
use App\Models\Validacion;
use App\Services\ClienteCore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes gerenciales (Super Admin, paso 10 del flujo ampliado): salud del
 * trámite por rango de fechas — SLA, tiempos por etapa, productividad y
 * rechazos — que el panel de indicadores en tiempo real no cubre.
 */
class ReportesController extends Controller
{
    public function __construct(private readonly ClienteCore $core) {}

    public function indicadores(Request $request): JsonResponse
    {
        $base = $this->filtrar($request);

        $solicitudes = (clone $base)->get([
            'id', 'estado', 'medio_acreditacion', 'dependencia_id', 'fecha_radicacion', 'fecha_limite_sla',
            'radicado', 'nombre_completo',
        ]);

        return response()->json([
            'filtros_aplicados' => [
                'desde' => $request->date('desde')?->toDateString(),
                'hasta' => $request->date('hasta')?->toDateString(),
                'dependencia_id' => $request->integer('dependencia_id') ?: null,
                'estado' => $request->string('estado')->trim()->value() ?: null,
                'medio_acreditacion' => $request->string('medio_acreditacion')->trim()->value() ?: null,
            ],
            'resumen' => $this->resumen($solicitudes),
            'sla' => $this->sla($solicitudes),
            'por_estado' => $this->porEstado($solicitudes),
            'por_medio' => $this->porMedio($solicitudes),
            'por_dependencia' => $this->porDependencia($solicitudes),
            'tendencia' => $this->tendencia($solicitudes),
            'productividad' => $this->productividad($request),
            'rechazos_recientes' => $this->rechazosRecientes($base),
            'vur' => $this->vur($request),
        ]);
    }

    /** Descarga CSV de los radicados que cumplen los filtros activos. */
    public function exportarRadicados(Request $request): StreamedResponse
    {
        $query = $this->filtrar($request)->orderBy('fecha_radicacion');

        try {
            $nombresDependencia = collect($this->core->dependencias())->pluck('nombre', 'id');
        } catch (\Throwable $e) {
            $nombresDependencia = collect();
        }

        $filename = 'radicados_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query, $nombresDependencia) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Radicado', 'Nombre', 'Documento', 'Estado', 'Medio', 'Dependencia',
                'Fecha radicación', 'Fecha límite SLA', 'Días hábiles restantes',
            ], escape: '\\');

            $query->chunk(200, function (Collection $chunk) use ($handle, $nombresDependencia) {
                foreach ($chunk as $s) {
                    fputcsv($handle, [
                        $s->radicado,
                        $s->nombre_completo,
                        $s->numero_identificacion,
                        $s->estado->label(),
                        $s->medio_acreditacion->label(),
                        $s->dependencia_id ? ($nombresDependencia[$s->dependencia_id] ?? "Dependencia #{$s->dependencia_id}") : 'Sin asignar',
                        $s->fecha_radicacion?->toDateString(),
                        $s->fecha_limite_sla?->toDateString(),
                        $s->diasRestantesSla(),
                    ], escape: '\\');
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Filtros comunes: rango de fecha de radicación, dependencia, estado y medio. */
    private function filtrar(Request $request): Builder
    {
        $query = Solicitud::query();

        if ($desde = $request->date('desde')) {
            $query->whereDate('fecha_radicacion', '>=', $desde);
        }

        if ($hasta = $request->date('hasta')) {
            $query->whereDate('fecha_radicacion', '<=', $hasta);
        }

        if ($dependenciaId = $request->integer('dependencia_id')) {
            $query->where('dependencia_id', $dependenciaId);
        }

        if ($estado = $request->string('estado')->trim()->value()) {
            $query->where('estado', $estado);
        }

        if ($medio = $request->string('medio_acreditacion')->trim()->value()) {
            $query->where('medio_acreditacion', $medio);
        }

        return $query;
    }

    private function resumen(Collection $solicitudes): array
    {
        $total = $solicitudes->count();
        $certificadas = $solicitudes->where('estado', EstadoSolicitud::Certificada)->count();
        $rechazadas = $solicitudes->where('estado', EstadoSolicitud::Rechazada)->count();

        return [
            'total' => $total,
            'certificadas' => $certificadas,
            'rechazadas' => $rechazadas,
            'pendientes' => $total - $certificadas - $rechazadas,
            'tiempo_promedio_dias' => $this->tiempoPromedioDias($solicitudes),
        ];
    }

    /** Promedio de días calendario entre radicación y expedición, solo para las solicitudes filtradas. */
    private function tiempoPromedioDias(Collection $solicitudes): ?float
    {
        $ids = $solicitudes->where('estado', EstadoSolicitud::Certificada)->pluck('id');

        if ($ids->isEmpty()) {
            return null;
        }

        $dias = Certificado::whereIn('solicitud_id', $ids)
            ->whereNotNull('fecha_expedicion')
            ->with('solicitud:id,fecha_radicacion')
            ->get()
            ->filter(fn ($c) => $c->solicitud?->fecha_radicacion)
            ->map(fn ($c) => $c->solicitud->fecha_radicacion->diffInDays($c->fecha_expedicion));

        return $dias->isEmpty() ? null : round($dias->avg(), 1);
    }

    /** Semáforo SLA de las solicitudes activas + % de certificados emitidos dentro del plazo. */
    private function sla(Collection $solicitudes): array
    {
        $activas = $solicitudes->whereNotIn('estado', [EstadoSolicitud::Certificada, EstadoSolicitud::Rechazada]);

        $semaforos = $activas->map(fn (Solicitud $s) => $s->semaforoSla());
        $vencidas = $activas->filter(fn (Solicitud $s) => ($s->diasRestantesSla() ?? 0) < 0)->count();

        $certificadasIds = $solicitudes->where('estado', EstadoSolicitud::Certificada)->pluck('id');
        $cumplimientoPct = null;

        if ($certificadasIds->isNotEmpty()) {
            $certs = Certificado::whereIn('solicitud_id', $certificadasIds)
                ->whereNotNull('fecha_expedicion')
                ->with('solicitud:id,fecha_limite_sla')
                ->get()
                ->filter(fn ($c) => $c->solicitud?->fecha_limite_sla);

            if ($certs->isNotEmpty()) {
                $dentroSla = $certs->filter(fn ($c) => $c->fecha_expedicion->lte($c->solicitud->fecha_limite_sla))->count();
                $cumplimientoPct = round($dentroSla / $certs->count() * 100, 1);
            }
        }

        return [
            'verde' => $semaforos->filter(fn ($c) => $c === 'green')->count(),
            'ambar' => $semaforos->filter(fn ($c) => $c === 'amber')->count(),
            'rojo' => $semaforos->filter(fn ($c) => $c === 'red')->count(),
            'vencidas' => $vencidas,
            'cumplimiento_pct' => $cumplimientoPct,
        ];
    }

    private function porEstado(Collection $solicitudes): array
    {
        $conteo = $solicitudes->countBy(fn (Solicitud $s) => $s->estado->value);

        return collect(EstadoSolicitud::cases())->map(fn ($e) => [
            'estado' => $e->value,
            'label' => $e->label(),
            'color' => $e->color(),
            'total' => (int) ($conteo[$e->value] ?? 0),
        ])->values()->all();
    }

    private function porMedio(Collection $solicitudes): array
    {
        $conteo = $solicitudes->countBy(fn (Solicitud $s) => $s->medio_acreditacion->value);

        return collect(MedioAcreditacion::cases())->map(fn ($m) => [
            'medio' => $m->value,
            'label' => $m->label(),
            'total' => (int) ($conteo[$m->value] ?? 0),
        ])->values()->all();
    }

    /** Distribución por dependencia (nombres resueltos contra el Core; degrada con gracia si no responde). */
    private function porDependencia(Collection $solicitudes): array
    {
        $conteo = $solicitudes->whereNotNull('dependencia_id')->countBy('dependencia_id');
        $sinAsignar = $solicitudes->whereNull('dependencia_id')->count();

        if ($conteo->isEmpty() && $sinAsignar === 0) {
            return [];
        }

        try {
            $nombres = collect($this->core->dependencias())->pluck('nombre', 'id');
        } catch (\Throwable $e) {
            $nombres = collect();
        }

        $items = $conteo->map(fn ($total, $id) => [
            'dependencia_id' => (int) $id,
            'nombre' => $nombres[$id] ?? "Dependencia #{$id}",
            'total' => (int) $total,
        ])->values();

        if ($sinAsignar > 0) {
            $items->push(['dependencia_id' => 0, 'nombre' => 'Sin dependencia asignada', 'total' => $sinAsignar]);
        }

        return $items->sortByDesc('total')->values()->all();
    }

    /** Radicados por día (rangos cortos) o por mes (rangos largos / sin filtro). */
    private function tendencia(Collection $solicitudes): array
    {
        $fechas = $solicitudes->pluck('fecha_radicacion')->filter();

        if ($fechas->isEmpty()) {
            return [];
        }

        $rangoDias = $fechas->min()->diffInDays($fechas->max());

        if ($rangoDias <= 31) {
            return $solicitudes->groupBy(fn (Solicitud $s) => $s->fecha_radicacion->format('Y-m-d'))
                ->map->count()
                ->sortKeys()
                ->map(fn ($total, $fecha) => [
                    'periodo' => $fecha,
                    'label' => Carbon::parse($fecha)->locale('es')->isoFormat('D MMM'),
                    'total' => $total,
                ])->values()->all();
        }

        return $solicitudes->groupBy(fn (Solicitud $s) => $s->fecha_radicacion->format('Y-m'))
            ->map->count()
            ->sortKeys()
            ->map(fn ($total, $periodo) => [
                'periodo' => $periodo,
                'label' => Carbon::parse($periodo.'-01')->locale('es')->isoFormat('MMM YY'),
                'total' => $total,
            ])->values()->all();
    }

    /** Top funcionarios por validaciones + firmas dentro del rango de fechas (filtro por fecha de la acción, no de radicación). */
    private function productividad(Request $request): array
    {
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');
        $dependenciaId = $request->integer('dependencia_id') ?: null;

        $validaciones = Validacion::query()
            ->whereNotNull('validado_por')
            ->when($desde, fn ($q) => $q->whereDate('validado_at', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('validado_at', '<=', $hasta))
            ->when($dependenciaId, fn ($q) => $q->whereHas('solicitud', fn ($s) => $s->where('dependencia_id', $dependenciaId)))
            ->selectRaw('validado_por, COUNT(*) as total')
            ->groupBy('validado_por')
            ->pluck('total', 'validado_por');

        $firmas = Certificado::query()
            ->whereNotNull('firmado_por')
            ->whereNotNull('fecha_expedicion')
            ->when($desde, fn ($q) => $q->whereDate('fecha_expedicion', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_expedicion', '<=', $hasta))
            ->when($dependenciaId, fn ($q) => $q->whereHas('solicitud', fn ($s) => $s->where('dependencia_id', $dependenciaId)))
            ->selectRaw('firmado_por, COUNT(*) as total')
            ->groupBy('firmado_por')
            ->pluck('total', 'firmado_por');

        $usuarioIds = $validaciones->keys()->merge($firmas->keys())->unique();

        if ($usuarioIds->isEmpty()) {
            return [];
        }

        $nombres = User::whereIn('id', $usuarioIds)->pluck('name', 'id');

        return $usuarioIds->map(fn ($id) => [
            'usuario_id' => (int) $id,
            'nombre' => $nombres[$id] ?? "Usuario #{$id}",
            'validaciones' => (int) ($validaciones[$id] ?? 0),
            'firmas' => (int) ($firmas[$id] ?? 0),
            'total' => (int) (($validaciones[$id] ?? 0) + ($firmas[$id] ?? 0)),
        ])->sortByDesc('total')->take(8)->values()->all();
    }

    /** Últimos rechazos dentro del filtro, con el motivo de la prevalidación que los originó. */
    private function rechazosRecientes(Builder $base): array
    {
        return (clone $base)
            ->where('estado', EstadoSolicitud::Rechazada->value)
            ->latest('fecha_radicacion')
            ->with(['validaciones' => fn ($q) => $q
                ->where('resultado', ResultadoValidacion::Rechaza->value)
                ->latest('validado_at'),
            ])
            ->limit(8)
            ->get()
            ->map(fn (Solicitud $s) => [
                'radicado' => $s->radicado,
                'nombre_completo' => $s->nombre_completo,
                'fecha_radicacion' => $s->fecha_radicacion?->toDateString(),
                'motivo' => $s->validaciones->first()?->observacion,
            ])->all();
    }

    /** Recibidos de VUR vs. cuántos ya se radicaron manualmente, en el rango de fechas. */
    private function vur(Request $request): array
    {
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');

        $query = RecibidoVur::query()
            ->when($desde, fn ($q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('created_at', '<=', $hasta));

        $total = (clone $query)->count();
        $radicados = (clone $query)->whereNotNull('solicitud_id')->count();

        return [
            'recibidos' => $total,
            'radicados' => $radicados,
            'pendientes' => $total - $radicados,
        ];
    }
}
