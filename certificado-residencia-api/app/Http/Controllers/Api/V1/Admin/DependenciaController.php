<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClienteCore;
use Illuminate\Http\JsonResponse;

class DependenciaController extends Controller
{
    public function __construct(protected ClienteCore $core)
    {
    }

    public function index(): JsonResponse
    {
        $data = collect($this->core->dependencias())
            ->sortBy('nombre')
            ->values();

        return response()->json(['data' => $data]);
    }
}
