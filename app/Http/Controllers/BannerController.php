<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Display a listing of active banners ordered by order field.
     */
    public function index(): AnonymousResourceCollection
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return BannerResource::collection($banners);
    }

    /**
     * Display a listing of all banners (admin).
     */
    public function all(): AnonymousResourceCollection
    {
        $banners = Banner::query()
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return BannerResource::collection($banners);
    }

    /**
     * Store a newly created banner.
     */
    public function store(StoreBannerRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('banners', $filename, 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $banner = Banner::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil ditambahkan',
            'data' => new BannerResource($banner),
        ], 201);
    }

    /**
     * Display the specified banner.
     */
    public function show(Banner $banner): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new BannerResource($banner),
        ]);
    }

    /**
     * Update the specified banner.
     */
    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($banner->image_url) {
                $oldPath = str_replace('/storage/', '', $banner->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            // Upload new image
            $image = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('banners', $filename, 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $banner->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil diperbarui',
            'data' => new BannerResource($banner->fresh()),
        ]);
    }

    /**
     * Remove the specified banner.
     */
    public function destroy(Banner $banner): JsonResponse
    {
        // Delete image if exists
        if ($banner->image_url) {
            $path = str_replace('/storage/', '', $banner->image_url);
            Storage::disk('public')->delete($path);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil dihapus',
        ]);
    }
}
