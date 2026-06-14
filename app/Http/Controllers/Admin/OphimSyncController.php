<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OphimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OphimSyncController extends Controller
{
    public function movies(Request $request, OphimService $ophim): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $result = $ophim->syncMovies($validated['page'] ?? 1, $validated['limit'] ?? 20);

        return response()->json($result, $result['success'] ? 200 : 502);
    }

    public function genres(OphimService $ophim): JsonResponse
    {
        $result = $ophim->syncGenres();

        return response()->json($result, $result['success'] ? 200 : 502);
    }
}
