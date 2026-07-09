<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoSolicitud;
use App\Enums\MedioAcreditacion;
use App\Http\Controllers\Controller;
use App\Models\Certificado;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Indicadores en tiempo real del trámite (paso 10 del flujo).
     */
    public function indicadores(Request $request): JsonResponse
    {
        $porEstado = Solicitud::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $total = (int) $porEstado->sum();
        $certificadas = (int) ($porEstado[EstadoSolicitud::Certificada->value] ?? 0);
        $rechazadas = (int) ($porEstado[EstadoSolicitud::Rechazada->value] ?? 0);
        $pendientes = $total - $certificadas - $rechazadas;

        return response()->json([
            'resumen' => [
                'total' => $total,
                'certificados_emitidos' => $certificadas,
                'pendientes' => $pendientes,
                'rechazadas' => $rechazadas,
                'tiempo_promedio_dias' => $this->tiempoPromedioDias(),
            ],
            'por_estado' => collect(EstadoSolicitud::cases())->map(fn ($e) => [
                'estado' => $e->value,
                'label' => $e->label(),
                'color' => $e->color(),
                'total' => (int) ($porEstado[$e->value] ?? 0),
            ])->values(),
            'por_medio' => $this->porMedio(),
            'por_sector' => $this->porSector(),
            'tendencia' => $this->tendenciaCertificados(),
            'bandeja_rol' => $this->bandejaRol($request),
        ]);
    }

    /** Promedio de días calendario entre radicación y expedición. */
    private function tiempoPromedioDias(): ?float
    {
        $certs = Certificado::with('solicitud:id,fecha_radicacion')
            ->whereNotNull('fecha_expedicion')
            ->get(['id', 'solicitud_id', 'fecha_expedicion']);

        if ($certs->isEmpty()) {
            return null;
        }

        $dias = $certs
            ->filter(fn ($c) => $c->solicitud?->fecha_radicacion)
            ->map(fn ($c) => $c->solicitud->fecha_radicacion->diffInDays($c->fecha_expedicion));

        return $dias->isEmpty() ? null : round($dias->avg(), 1);
    }

    /** Distribución por tipo de soporte (medio de acreditación). */
    private function porMedio(): array
    {
        $conteo = Solicitud::query()
            ->selectRaw('medio_acreditacion, COUNT(*) as total')
            ->groupBy('medio_acreditacion')
            ->pluck('total', 'medio_acreditacion');

        return collect(MedioAcreditacion::cases())->map(fn ($m) => [
            'medio' => $m->value,
            'label' => $m->label(),
            'total' => (int) ($conteo[$m->value] ?? 0),
        ])->values()->all();
    }

    /** Top 6 barrios/veredas por número de solicitudes. */
    private function porSector(): array
    {
        return Solicitud::query()
            ->selectRaw('barrio_vereda_sector as sector, COUNT(*) as total')
            ->groupBy('barrio_vereda_sector')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($r) => ['sector' => $r->sector, 'total' => (int) $r->total])
            ->all();
    }

    /** Certificados expedidos en los últimos 6 meses (agrupado en PHP para ser agnóstico de BD). */
    private function tendenciaCertificados(): array
    {
        $desde = now()->startOfMonth()->subMonths(5);

        $emitidos = Certificado::query()
            ->whereNotNull('fecha_expedicion')
            ->where('fecha_expedicion', '>=', $desde)
            ->get(['fecha_expedicion'])
            ->groupBy(fn ($c) => $c->fecha_expedicion->format('Y-m'))
            ->map->count();

        $meses = [];
        for ($i = 0; $i < 6; $i++) {
            $mes = $desde->copy()->addMonths($i);
            $meses[] = [
                'periodo' => $mes->format('Y-m'),
                'label' => $mes->locale('es')->isoFormat('MMM'),
                'total' => (int) ($emitidos[$mes->format('Y-m')] ?? 0),
            ];
        }

        return $meses;
    }

    /** Contadores rápidos según el rol del usuario. */
    private function bandejaRol(Request $request): array
    {
        $user = $request->user();
        $hoy = Carbon::today();
        $stats = [];

        if ($user->hasRole('alcalde') || $user->hasRole('super_admin')) {
            $stats['pendientes_firma'] = Solicitud::where('estado', EstadoSolicitud::Preaprobada->value)->count();
            $stats['firmadas_hoy'] = Certificado::whereDate('fecha_expedicion', $hoy)->count();
        }
        if ($user->hasRole('recepcionista') || $user->hasRole('super_admin')) {
            $stats['radicadas_hoy'] = Solicitud::whereDate('fecha_radicacion', $hoy)->count();
        }
        if ($user->hasRole('operador') || $user->hasRole('super_admin')) {
            $stats['en_validacion'] = Solicitud::where('estado', EstadoSolicitud::EnValidacion->value)->count();
        }

        return $stats;
    }
}
