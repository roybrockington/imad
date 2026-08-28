<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    /**
     * Display a listing of published career opportunities.
     */
    public function index(): JsonResponse
    {
        $careers = Career::where('published', true)
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($careers);
    }

    /**
     * Display all career opportunities for admin view.
     */
    public function adminIndex(): JsonResponse
    {
        $careers = Career::orderBy('created_at', 'desc')->get();

        return response()->json($careers);
    }

    /**
     * Store a newly created career opportunity.
     * Auto-translates German content to other languages using DeepL.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'location' => 'required|string|max:255',
            'published' => 'boolean',
            // German fields (required - default admin input language)
            'position_de' => 'required|string|max:255',
            'tasks_de' => 'required|string',
            'profile_de' => 'required|string',
            'expectations_de' => 'required|string',
            // Other language fields (optional - will be auto-generated if not provided)
            'position_en' => 'nullable|string|max:255',
            'position_fr' => 'nullable|string|max:255',
            'position_nl' => 'nullable|string|max:255',
            'position_pl' => 'nullable|string|max:255',
            'tasks_en' => 'nullable|string',
            'tasks_fr' => 'nullable|string',
            'tasks_nl' => 'nullable|string',
            'tasks_pl' => 'nullable|string',
            'profile_en' => 'nullable|string',
            'profile_fr' => 'nullable|string',
            'profile_nl' => 'nullable|string',
            'profile_pl' => 'nullable|string',
            'expectations_en' => 'nullable|string',
            'expectations_fr' => 'nullable|string',
            'expectations_nl' => 'nullable|string',
            'expectations_pl' => 'nullable|string',
        ]);

        // Auto-translate German content to other languages if not provided
        try {
            $translations = $this->translationService->translateCareerFields($validated);
            $validated = array_merge($validated, $translations);
        } catch (\Exception $e) {
            // Log error but continue - German content is already present
            \Log::error('Career translation failed during creation', [
                'error' => $e->getMessage(),
                'career_data' => $validated
            ]);
        }

        $career = Career::create($validated);

        return response()->json($career, 201);
    }

    /**
     * Display the specified career opportunity.
     */
    public function show(Career $career): JsonResponse
    {
        return response()->json($career);
    }

    /**
     * Update the specified career opportunity.
     * Auto-translates German content to other languages using DeepL if German fields are updated.
     */
    public function update(Request $request, Career $career): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'location' => 'sometimes|string|max:255',
            'published' => 'sometimes|boolean',
            // German fields
            'position_de' => 'sometimes|string|max:255',
            'tasks_de' => 'sometimes|string',
            'profile_de' => 'sometimes|string',
            'expectations_de' => 'sometimes|string',
            // Other language fields (optional)
            'position_en' => 'sometimes|nullable|string|max:255',
            'position_fr' => 'sometimes|nullable|string|max:255',
            'position_nl' => 'sometimes|nullable|string|max:255',
            'position_pl' => 'sometimes|nullable|string|max:255',
            'tasks_en' => 'sometimes|nullable|string',
            'tasks_fr' => 'sometimes|nullable|string',
            'tasks_nl' => 'sometimes|nullable|string',
            'tasks_pl' => 'sometimes|nullable|string',
            'profile_en' => 'sometimes|nullable|string',
            'profile_fr' => 'sometimes|nullable|string',
            'profile_nl' => 'sometimes|nullable|string',
            'profile_pl' => 'sometimes|nullable|string',
            'expectations_en' => 'sometimes|nullable|string',
            'expectations_fr' => 'sometimes|nullable|string',
            'expectations_nl' => 'sometimes|nullable|string',
            'expectations_pl' => 'sometimes|nullable|string',
        ]);

        // Check if any German fields were updated
        $germanFieldsUpdated = isset($validated['position_de'])
            || isset($validated['tasks_de'])
            || isset($validated['profile_de'])
            || isset($validated['expectations_de']);

        // Auto-translate if German fields were updated
        if ($germanFieldsUpdated) {
            try {
                // Merge with existing German values if not all fields were provided
                $germanData = [
                    'position_de' => $validated['position_de'] ?? $career->position_de,
                    'tasks_de' => $validated['tasks_de'] ?? $career->tasks_de,
                    'profile_de' => $validated['profile_de'] ?? $career->profile_de,
                    'expectations_de' => $validated['expectations_de'] ?? $career->expectations_de,
                ];

                $translations = $this->translationService->translateCareerFields($germanData);
                $validated = array_merge($validated, $translations);
            } catch (\Exception $e) {
                // Log error but continue - German content is already present
                \Log::error('Career translation failed during update', [
                    'career_id' => $career->id,
                    'error' => $e->getMessage(),
                    'career_data' => $validated
                ]);
            }
        }

        $career->update($validated);

        return response()->json($career);
    }

    /**
     * Remove the specified career opportunity.
     */
    public function destroy(Career $career): JsonResponse
    {
        $career->delete();

        return response()->json(null, 204);
    }
}
