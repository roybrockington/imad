<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlideController extends Controller
{
    /**
     * Display a listing of active slides for public view.
     */
    public function index(): JsonResponse
    {
        $slides = Slide::active()->ordered()->get();

        return response()->json($slides);
    }

    /**
     * Display all slides for admin view.
     */
    public function adminIndex(): JsonResponse
    {
        $slides = Slide::ordered()->get();

        return response()->json($slides);
    }

    /**
     * Store a newly created slide.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'caption_en' => 'nullable|string',
            'caption_de' => 'nullable|string',
            'caption_nl' => 'nullable|string',
            'caption_pl' => 'nullable|string',
            'caption_fr' => 'nullable|string',
            'background' => 'nullable|string|max:500',
            'video' => 'nullable|string|max:500',
            'link' => 'nullable|string|max:500',
            'order' => 'nullable|integer',
            'active' => 'nullable|boolean',
        ]);

        $slide = Slide::create($validated);

        return response()->json($slide, 201);
    }

    /**
     * Display the specified slide.
     */
    public function show(Slide $slide): JsonResponse
    {
        return response()->json($slide);
    }

    /**
     * Update the specified slide.
     */
    public function update(Request $request, Slide $slide): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|nullable|string|max:255',
            'caption_en' => 'sometimes|nullable|string',
            'caption_de' => 'sometimes|nullable|string',
            'caption_nl' => 'sometimes|nullable|string',
            'caption_pl' => 'sometimes|nullable|string',
            'caption_fr' => 'sometimes|nullable|string',
            'background' => 'sometimes|nullable|string|max:500',
            'video' => 'sometimes|nullable|string|max:500',
            'link' => 'sometimes|nullable|string|max:500',
            'order' => 'sometimes|nullable|integer',
            'active' => 'sometimes|nullable|boolean',
        ]);

        $slide->update($validated);

        return response()->json($slide);
    }

    /**
     * Remove the specified slide.
     */
    public function destroy(Slide $slide): JsonResponse
    {
        $slide->delete();

        return response()->json(null, 204);
    }

    /**
     * Reorder slides based on provided order array.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slides' => 'required|array',
            'slides.*.id' => 'required|exists:slides,id',
            'slides.*.order' => 'required|integer',
        ]);

        foreach ($validated['slides'] as $slideData) {
            Slide::where('id', $slideData['id'])
                ->update(['order' => $slideData['order']]);
        }

        $slides = Slide::ordered()->get();

        return response()->json($slides);
    }
}
