<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaundryCategoryRequest;
use App\Http\Requests\UpdateLaundryCategoryRequest;
use App\Http\Resources\LaundryCategoryResource;
use App\Models\LaundryCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LaundryCategoryController extends Controller
{
    /**
     * Display a listing of categories for the authenticated user's branch.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch) {
            return LaundryCategoryResource::collection(collect([]));
        }

        $categories = LaundryCategory::query()
            ->where('branch_id', $userBranch->id)
            ->with('items')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return LaundryCategoryResource::collection($categories);
    }

    /**
     * Get categories for a specific branch (public - for mobile app).
     */
    public function forBranch(Request $request, int $branchId): JsonResponse
    {
        $categories = LaundryCategory::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->with(['activeItems' => function ($query) {
                $query->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => LaundryCategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreLaundryCategoryRequest $request): JsonResponse
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki cabang',
            ], 403);
        }

        $category = LaundryCategory::create([
            'branch_id' => $userBranch->id,
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data' => new LaundryCategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(LaundryCategory $category): JsonResponse
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch || $category->branch_id !== $userBranch->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan',
            ], 404);
        }

        $category->load('items');

        return response()->json([
            'success' => true,
            'data' => new LaundryCategoryResource($category),
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateLaundryCategoryRequest $request, LaundryCategory $category): JsonResponse
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch || $category->branch_id !== $userBranch->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan',
            ], 404);
        }

        $category->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data' => new LaundryCategoryResource($category->fresh()),
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(LaundryCategory $category): JsonResponse
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch || $category->branch_id !== $userBranch->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus',
        ]);
    }
}
