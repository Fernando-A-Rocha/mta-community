<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LegacyResourceVersionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $resourceName = trim((string) $request->query('version', ''));

        if ($resourceName === '') {
            return $this->buildResponse('', 0, null);
        }

        $resource = Resource::query()
            ->with('currentVersion')
            ->whereRaw('LOWER(name) = ?', [Str::lower($resourceName)])
            ->first();

        if ($resource === null) {
            return $this->buildResponse($resourceName, 0, null);
        }

        $version = $resource->currentVersion;

        if ($version === null) {
            $version = $resource->versions()
                ->select('id', 'resource_id', 'version', 'created_at')
                ->latest('created_at')
                ->first();
        }

        if ($version === null) {
            return $this->buildResponse($resource->name, 0, null);
        }

        return $this->buildResponse($resource->name, $version->version, (int) $resource->id);
    }

    private function buildResponse(string $resourceName, string|int $version, ?int $communityId): JsonResponse
    {
        return response()->json([
            $resourceName,
            $version,
            $communityId,
        ]);
    }
}
