<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Cargar la imagen de firma del usuario (Alcalde).
     */
    public function subirFirma(Request $request): JsonResponse
    {
        $request->validate([
            'firma' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $user = $request->user();

        // Reemplaza la firma anterior si existe
        if ($user->firma_path) {
            Storage::disk('local')->delete($user->firma_path);
        }

        $path = $request->file('firma')->storeAs('firmas', "user_{$user->id}.png", 'local');
        $user->forceFill(['firma_path' => $path])->save();

        return response()->json([
            'message' => 'Firma cargada correctamente.',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Cargar la foto de perfil del usuario autenticado. La ruta vive en la
     * BD de CDR (columna foto_path); el archivo en sí vive en el volumen
     * (disco "local"), igual que la firma.
     */
    public function subirFoto(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->foto_path) {
            Storage::disk('local')->delete($user->foto_path);
        }

        $extension = $request->file('foto')->getClientOriginalExtension();
        $path = $request->file('foto')->storeAs('fotos-perfil', "user_{$user->id}.{$extension}", 'local');
        $user->forceFill(['foto_path' => $path])->save();

        return response()->json([
            'message' => 'Foto de perfil cargada correctamente.',
            'user' => new UserResource($user),
        ]);
    }

    /** Sirve la foto de perfil del usuario autenticado (requiere token, no es una URL pública). */
    public function verFoto(Request $request): StreamedResponse
    {
        $path = $request->user()->foto_path;
        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /** Sirve la imagen de firma del usuario autenticado (requiere token, no es una URL pública). */
    public function verFirma(Request $request): StreamedResponse
    {
        $path = $request->user()->firma_path;
        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
