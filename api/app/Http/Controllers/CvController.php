<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvRequest;
use App\Http\Resources\CvResource;
use App\Models\Cv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CvController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $cvs = $request->user()->cvs()->latest()->get();

        return CvResource::collection($cvs);
    }

    public function store(StoreCvRequest $request): JsonResponse
    {
        if ($request->user()->cvs()->count() >= 10) {
            return response()->json([
                'message' => 'Maksimal 10 CV per user.',
                'errors' => ['title' => ['Maksimal 10 CV per user.']],
            ], 422);
        }

        $cv = $request->user()->cvs()->create($request->validated());

        return response()->json(['cv' => new CvResource($cv)], 201);
    }

    public function show(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        return response()->json(['cv' => new CvResource($cv)]);
    }

    public function update(StoreCvRequest $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $cv->update($request->validated());

        return response()->json(['cv' => new CvResource($cv)]);
    }

    public function destroy(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $cv->delete();

        return response()->json(null, 204);
    }

    private function authorizeOwner(Request $request, Cv $cv): void
    {
        if ($cv->user_id !== $request->user()->id) {
            abort(403, 'Bukan pemilik CV ini.');
        }
    }
}
