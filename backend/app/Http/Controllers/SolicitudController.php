<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SolicitudController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->query();
        if (!$this->canManage($request)) {
            $query->where('solicitudes.solicitante_id', $request->user()->id);
        }

        return response()->json($query->orderByDesc('solicitudes.fecha_solicitud')->get());
    }

    public function show(Request $request, int $id)
    {
        $solicitud = $this->query()->where('solicitudes.id', $id)->first();
        abort_unless($solicitud, 404);
        abort_unless($this->canManage($request) || $solicitud->solicitante_id === $request->user()->id, 403);

        return response()->json($solicitud);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo' => ['required', Rule::in(['servicio', 'informacion', 'acceso'])],
            'asunto' => ['required', 'string', 'max:160'],
            'descripcion' => ['required', 'string', 'max:3000'],
            'prioridad' => ['nullable', Rule::in(['baja', 'media', 'alta', 'critica'])],
        ]);

        $id = DB::transaction(function () use ($request, $data) {
            $now = now();
            $id = DB::table('solicitudes')->insertGetId([
                'solicitante_id' => $request->user()->id,
                'tipo' => $data['tipo'],
                'asunto' => $data['asunto'],
                'descripcion' => $data['descripcion'],
                'prioridad' => $data['prioridad'] ?? 'media',
                'estado' => 'pendiente',
                'fecha_solicitud' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->logEvent($id, $request->user()->id, 'creada');

            return $id;
        });

        return response()->json($this->query()->where('solicitudes.id', $id)->first(), 201);
    }

    public function asignar(Request $request, int $id)
    {
        abort_unless($this->canManage($request), 403);
        $data = $request->validate([
            'responsable_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'estado' => ['nullable', Rule::in(['en_revision'])],
        ]);

        DB::transaction(function () use ($request, $data, $id) {
            $solicitud = DB::table('solicitudes')->where('id', $id)->lockForUpdate()->first();
            abort_unless($solicitud, 404);
            abort_unless(in_array($solicitud->estado, ['pendiente', 'en_revision'], true), 409);

            $responsable = DB::table('users')->where('id', $data['responsable_id'])->first();
            abort_if($responsable?->role_id === null && $responsable?->rol !== 'admin', 422);

            DB::table('solicitudes')->where('id', $id)->update([
                'responsable_id' => $data['responsable_id'],
                'estado' => 'en_revision',
                'updated_at' => now(),
            ]);
            $this->logEvent($id, $request->user()->id, 'asignada', null, 'Responsable: '.$data['responsable_id']);
        });

        return response()->json($this->query()->where('solicitudes.id', $id)->first());
    }

    public function resolver(Request $request, int $id)
    {
        abort_unless($this->canManage($request), 403);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['aprobada', 'rechazada'])],
            'resolucion' => ['required', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($request, $data, $id) {
            $solicitud = DB::table('solicitudes')->where('id', $id)->lockForUpdate()->first();
            abort_unless($solicitud, 404);
            abort_unless(in_array($solicitud->estado, ['pendiente', 'en_revision'], true), 409);
            abort_unless($solicitud->responsable_id, 422, 'Asigne un responsable antes de resolver.');

            $now = now();
            DB::table('solicitudes')->where('id', $id)->update([
                'estado' => $data['decision'] === 'aprobada' ? 'resuelta' : 'rechazada',
                'decision' => $data['decision'],
                'resolucion' => $data['resolucion'],
                'decidido_por' => $request->user()->id,
                'decidido_at' => $now,
                'resuelto_at' => $now,
                'updated_at' => $now,
            ]);
            $this->logEvent($id, $request->user()->id, 'resuelta', $data['decision'], $data['resolucion']);
        });

        // An approved access request is only a recorded decision. Role changes use the separate user administration flow.
        return response()->json($this->query()->where('solicitudes.id', $id)->first());
    }

    public function eventos(Request $request, int $id)
    {
        $solicitud = DB::table('solicitudes')->find($id);
        abort_unless($solicitud, 404);
        abort_unless($this->canManage($request) || $solicitud->solicitante_id === $request->user()->id, 403);

        return response()->json(DB::table('solicitud_eventos')->where('solicitud_id', $id)->orderBy('id')->get());
    }

    private function canManage(Request $request): bool
    {
        return in_array($request->user()->role_name, ['admin', 'gerente'], true)
            && $request->user()->hasPermission('solicitudes.gestionar');
    }

    private function query()
    {
        return DB::table('solicitudes')
            ->leftJoin('users as solicitante', 'solicitante.id', '=', 'solicitudes.solicitante_id')
            ->leftJoin('users as responsable', 'responsable.id', '=', 'solicitudes.responsable_id')
            ->select('solicitudes.*', 'solicitante.name as solicitante_nombre', 'responsable.name as responsable_nombre');
    }

    private function logEvent(int $id, int $actorId, string $evento, ?string $decision = null, ?string $detalle = null): void
    {
        DB::table('solicitud_eventos')->insert([
            'solicitud_id' => $id,
            'actor_id' => $actorId,
            'evento' => $evento,
            'decision' => $decision,
            'detalle' => $detalle,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
