<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'user' => new UserResource($user->load('dependencia')),
        ]);
    }
}
