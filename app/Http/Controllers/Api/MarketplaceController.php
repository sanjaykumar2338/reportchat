<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MarketplaceController extends Controller
{
    public function categories()
    {
        return response()->json([
            'status' => 'success',
            'data' => MarketplaceCategory::all(['id', 'name', 'icon']),
        ]);
    }

    public function index(Request $request)
    {
        $query = MarketplaceListing::where('is_active', true);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $listings = $query->latest()->get();
        $listings->transform(function ($listing) {
            $listing->images = $this->fullImageUrls($listing->images);
            return $listing;
        });

        return response()->json([
            'status' => 'success',
            'data' => $listings,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateListing($request);

        $listing = new MarketplaceListing($data);
        $listing->user_id = Auth::id();
        $listing->is_active = true;
        $listing->published_at = now();
        $listing->ends_at = now()->addDays(14);
        $listing->save();

        $listing->images = $this->handleBase64Images($request->images ?? []);
        $listing->save();

        $listing->images = $this->fullImageUrls($listing->images);

        return response()->json([
            'message' => 'Listing created successfully.',
            'data' => $listing,
        ]);
    }

    public function show($id)
    {
        $listing = MarketplaceListing::findOrFail($id);
        $listing->images = $this->fullImageUrls($listing->images);

        return response()->json(['data' => $listing]);
    }

    public function update(Request $request, $id)
    {
        $listing = MarketplaceListing::where('user_id', Auth::id())->findOrFail($id);
        $data = $this->validateListing($request);

        $listing->update($data);

        if ($request->has('images')) {
            $listing->images = $this->handleBase64Images($request->images);
            $listing->save();
        }

        $listing->images = $this->fullImageUrls($listing->images);

        return response()->json([
            'message' => 'Listing updated successfully.',
            'data' => $listing,
        ]);
    }

    public function destroy($id)
    {
        $listing = MarketplaceListing::where('user_id', Auth::id())->findOrFail($id);
        $listing->delete();

        return response()->json(['message' => 'Listing deleted.']);
    }

    public function toggleStatus($id)
    {
        $listing = MarketplaceListing::where('user_id', Auth::id())->findOrFail($id);
        $listing->is_active = !$listing->is_active;

        if ($listing->is_active) {
            $listing->published_at = now();
            $listing->ends_at = now()->addDays(14);
        }

        $listing->save();
        $listing->images = $this->fullImageUrls($listing->images);

        return response()->json([
            'message' => $listing->is_active ? 'Listing activated.' : 'Listing deactivated.',
            'data' => $listing,
        ]);
    }

    public function republish($id)
    {
        $listing = MarketplaceListing::where('user_id', Auth::id())->findOrFail($id);
        $listing->published_at = now();
        $listing->ends_at = now()->addDays(14);
        $listing->is_active = true;
        $listing->save();

        $listing->images = $this->fullImageUrls($listing->images);

        return response()->json([
            'message' => 'Listing republished for 14 days.',
            'data' => $listing,
        ]);
    }

    public function myListings()
    {
        $listings = MarketplaceListing::where('user_id', Auth::id())->latest()->get();

        $listings->transform(function ($listing) {
            $listing->images = $this->fullImageUrls($listing->images);
            return $listing;
        });

        return response()->json([
            'status' => 'success',
            'data' => $listings,
        ]);
    }

   private function validateListing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:marketplace_categories,id',
            'price' => 'required|numeric|min:0',
            'whatsapp' => ['required', 'regex:/^\d{10}$/'],
            'images' => 'nullable|array',
            'images.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        return $validator->validated();
    }

    private function handleBase64Images(array $images)
    {
        $paths = [];

        foreach ($images as $base64Image) {
            if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                continue; // Skip invalid image string
            }

            $imageData = base64_decode(substr($base64Image, strpos($base64Image, ',') + 1));

            if ($imageData === false) {
                continue; // Skip if decoding failed
            }

            $extension = strtolower($type[1]); // jpg, png, etc.
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $path = 'marketplace_images/' . $filename;

            Storage::disk('public')->put($path, $imageData);
            $paths[] = $path;
        }

        return json_encode($paths);
    }

    private function fullImageUrls($images)
    {
        $decoded = json_decode($images, true);
        if (!$decoded || !is_array($decoded)) return [];

        return array_map(function ($path) {
            return asset('storage/' . ltrim($path, '/'));
        }, $decoded);
    }
}