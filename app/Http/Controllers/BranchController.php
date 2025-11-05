<?php

namespace App\Http\Controllers;

use App\Http\Requests\NearbyBranchRequest;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /**
     * Display a listing of the branches.
     */
    public function index(): AnonymousResourceCollection
    {
        $branches = Branch::query()
            ->where('user_id', auth()->id())
            ->with('user')
            ->latest()
            ->get();

        return BranchResource::collection($branches);
    }

    /**
     * Store a newly created branch.
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = Branch::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'detail_address' => $request->detail_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'phone' => $request->phone,
            'working_hours' => $request->working_hours ?? 10,
            'price_per_kg' => $request->price_per_kg ?? 5000,
            'image_url' => $request->image_url,
            'pickup_gojek' => $request->pickup_gojek ?? false,
            'pickup_grab' => $request->pickup_grab ?? false,
            'pickup_free' => $request->pickup_free ?? false,
            'pickup_free_schedule' => $request->pickup_free_schedule,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cabang berhasil ditambahkan',
            'data' => new BranchResource($branch),
        ], 201);
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch): JsonResponse
    {
        if ($branch->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke cabang ini',
            ], 403);
        }

        $branch->load('user');

        return response()->json([
            'success' => true,
            'data' => new BranchResource($branch),
        ]);
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        if ($branch->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke cabang ini',
            ], 403);
        }

        $branch->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cabang berhasil diperbarui',
            'data' => new BranchResource($branch->fresh()),
        ]);
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        if ($branch->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke cabang ini',
            ], 403);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cabang berhasil dihapus',
        ]);
    }

    /**
     * Get nearby branches based on latitude, longitude and radius.
     */
    public function nearby(NearbyBranchRequest $request): JsonResponse
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->validated()['radius'];

        $branches = Branch::query()
            ->select(
                'branches.*',
                DB::raw('
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                    sin(radians(latitude)))) AS distance
                ')
            )
            ->setBindings([$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius / 1000) // Convert meter to km
            ->with('user')
            ->orderBy('distance')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar cabang terdekat',
            'meta' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_meter' => $radius,
                'radius_km' => $radius / 1000,
                'total' => $branches->count(),
            ],
            'data' => BranchResource::collection($branches),
        ]);
    }
}
