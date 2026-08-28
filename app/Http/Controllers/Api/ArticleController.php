<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Article::query()
            ->with(['author:id,name', 'brands:id,name'])
            ->published()
            ->orderBy('published_at', 'desc');

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->whereHas('brands', function ($q) use ($request) {
                $q->where('brands.id', $request->input('brand_id'));
            });
        }

        // Search by title or excerpt
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $articles = $query->paginate(12);

        return response()->json($articles);
    }

    /**
     * Display a listing of all articles (admin).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Article::query()
            ->with(['author:id,name', 'brands:id,name'])
            ->orderBy('created_at', 'desc');

        // Filter by published status
        if ($request->has('published')) {
            $query->where('published', $request->boolean('published'));
        }

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->whereHas('brands', function ($q) use ($request) {
                $q->where('brands.id', $request->input('brand_id'));
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $articles = $query->paginate(15);

        return response()->json($articles);
    }

    /**
     * Store a newly created article.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:articles,slug',
            'excerpt' => 'nullable|string',
            'body' => 'required|string',
            'featured_image' => 'nullable|image|max:5120', // 5MB max
            'published' => 'boolean',
            'published_at' => 'nullable|date',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'exists:brands,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['author_id'] = $request->user()->id;

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('articles', $originalName, 'public');
            $data['featured_image'] = $path;
        }

        // Set published_at if publishing
        if (!empty($data['published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $article = Article::create($data);

        // Attach brands if provided
        if (!empty($data['brand_ids'])) {
            $article->brands()->attach($data['brand_ids']);
        }

        $article->load(['author:id,name', 'brands:id,name']);

        return response()->json([
            'message' => 'Article created successfully',
            'article' => $article
        ], 201);
    }

    /**
     * Display the specified article by slug (public).
     */
    public function show(string $slug): JsonResponse
    {
        $article = Article::where('slug', $slug)
            ->with(['author:id,name', 'brands:id,name'])
            ->published()
            ->firstOrFail();

        return response()->json($article);
    }

    /**
     * Display the specified article by ID (admin).
     */
    public function adminShow(int $id): JsonResponse
    {
        $article = Article::with(['author:id,name', 'brands:id,name'])
            ->findOrFail($id);

        return response()->json($article);
    }

    /**
     * Update the specified article.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $article = Article::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|unique:articles,slug,' . $id,
            'excerpt' => 'nullable|string',
            'body' => 'sometimes|required|string',
            'featured_image' => 'nullable|image|max:5120',
            'published' => 'boolean',
            'published_at' => 'nullable|date',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'exists:brands,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }

            $file = $request->file('featured_image');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('articles', $originalName, 'public');
            $data['featured_image'] = $path;
        }

        // Set published_at when publishing for the first time
        if (!empty($data['published']) && empty($article->published_at)) {
            $data['published_at'] = now();
        }

        $article->update($data);

        // Sync brands if provided
        if (isset($data['brand_ids'])) {
            $article->brands()->sync($data['brand_ids']);
        }

        $article->load(['author:id,name', 'brands:id,name']);

        return response()->json([
            'message' => 'Article updated successfully',
            'article' => $article
        ]);
    }

    /**
     * Remove the specified article.
     */
    public function destroy(int $id): JsonResponse
    {
        $article = Article::findOrFail($id);

        // Delete featured image if exists
        if ($article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
        }

        $article->delete();

        return response()->json([
            'message' => 'Article deleted successfully'
        ]);
    }

    /**
     * Upload featured image for article.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('image');
        $originalName = $file->getClientOriginalName();
        $path = $file->storeAs('articles', $originalName, 'public');

        return response()->json([
            'message' => 'Image uploaded successfully',
            'path' => $path,
            'url' => Storage::url($path)
        ]);
    }
}
