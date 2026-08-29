<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function summary(Request $request, AiService $ai): JsonResponse
    {
        $request->validate([
            'cv_id' => 'required|integer|exists:cvs,id',
            'language' => 'nullable|in:id,en',
            'data' => 'nullable|array',
        ]);

        $cv = Cv::findOrFail($request->input('cv_id'));

        if ($cv->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $lang = $request->input('language') ?? $cv->language ?? 'id';
        $data = $request->input('data') ?? $cv->data ?? [];

        try {
            $summary = $ai->generateSummary($data, $lang);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'AI_API_KEY')) {
                return response()->json(['message' => 'AI belum dikonfigurasi'], 503);
            }
            return response()->json(['message' => 'AI service unavailable'], 502);
        }

        return response()->json(['summary' => $summary]);
    }
}
