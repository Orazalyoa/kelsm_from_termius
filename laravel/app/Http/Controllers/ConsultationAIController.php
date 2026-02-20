<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Services\ConsultationAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class ConsultationAIController extends Controller
{
    protected $analysisService;

    public function __construct(ConsultationAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Analyze a consultation using AI
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function analyze(Request $request, $id): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($id);

            // Check if user can access this consultation
            if (!$consultation->canBeAccessedBy($request->user())) {
                return response()->json([
                    'message' => 'Access denied',
                ], 403);
            }

            $language = $request->input('language', 'en');

            $analysis = $this->analysisService->analyzeConsultation($consultation, $language);

            return response()->json([
                'message' => 'Consultation analyzed successfully',
                'analysis' => $analysis,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to analyze consultation', [
                'consultation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to analyze consultation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a summary of a consultation
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function summarize(Request $request, $id): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($id);

            // Check if user can access this consultation
            if (!$consultation->canBeAccessedBy($request->user())) {
                return response()->json([
                    'message' => 'Access denied',
                ], 403);
            }

            $language = $request->input('language', 'en');

            $summary = $this->analysisService->summarizeConsultation($consultation, $language);

            return response()->json([
                'message' => 'Summary generated successfully',
                'summary' => $summary,
                'consultation_id' => $consultation->id,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to summarize consultation', [
                'consultation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to generate summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suggest priority for a consultation
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function suggestPriority(Request $request, $id): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($id);

            // Check if user can access this consultation
            if (!$consultation->canBeAccessedBy($request->user())) {
                return response()->json([
                    'message' => 'Access denied',
                ], 403);
            }

            $language = $request->input('language', 'en');

            $suggestedPriority = $this->analysisService->suggestPriority($consultation, $language);

            return response()->json([
                'message' => 'Priority suggested successfully',
                'suggested_priority' => $suggestedPriority,
                'current_priority' => $consultation->priority,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to suggest priority', [
                'consultation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to suggest priority',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

