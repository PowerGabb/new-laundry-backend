<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaundryItemRequest;
use App\Http\Requests\UpdateLaundryItemRequest;
use App\Http\Resources\LaundryItemResource;
use App\Models\LaundryCategory;
use App\Models\LaundryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LaundryItemController extends Controller
{
    /**
     * Display a listing of items for a category.
     */
    public function index(LaundryCategory $category): AnonymousResourceCollection
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch || $category->branch_id !== $userBranch->id) {
            return LaundryItemResource::collection(collect([]));
        }

        $items = $category->items()->orderBy('sort_order')->orderBy('name')->get();

        return LaundryItemResource::collection($items);
    }

    /**
     * Store a newly created item in a category.
     */
    public function store(StoreLaundryItemRequest $request, LaundryCategory $category): JsonResponse
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch || $category->branch_id !== $userBranch->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan',
            ], 404);
        }

        $item = LaundryItem::create([
            'category_id' => $category->id,
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'unit' => $request->unit,
            'price' => $request->price,
            'min_quantity' => $request->min_quantity ?? 1,
            'estimated_duration_hours' => $request->estimated_duration_hours ?? 24,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil ditambahkan',
            'data' => new LaundryItemResource($item),
        ], 201);
    }

    /**
     * Display the specified item.
     */
    public function show(LaundryCategory $category, LaundryItem $item): JsonResponse
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch || $category->branch_id !== $userBranch->id || $item->category_id !== $category->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new LaundryItemResource($item),
        ]);
    }

    /**
     * Update the specified item.
     */
    public function update(UpdateLaundryItemRequest $request, LaundryCategory $category, LaundryItem $item): JsonResponse
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch || $category->branch_id !== $userBranch->id || $item->category_id !== $category->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan',
            ], 404);
        }

        $item->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil diperbarui',
            'data' => new LaundryItemResource($item->fresh()),
        ]);
    }

    /**
     * Remove the specified item.
     */
    public function destroy(LaundryCategory $category, LaundryItem $item): JsonResponse
    {
        $userBranch = auth()->user()->branches()->first();

        if (! $userBranch || $category->branch_id !== $userBranch->id || $item->category_id !== $category->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus',
        ]);
    }
}
