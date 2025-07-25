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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
        Log::info('Marketplace Listing Request:', $request->all());

        // Step 1: Deactivate listings older than 14 days (Mexico timezone)
        $cutoffDate = Carbon::now('America/Mexico_City')->subDays(14);
        MarketplaceListing::where('is_active', 1)
            ->where('created_at', '<', $cutoffDate)
            ->update(['is_active' => 0]);

        // Step 2: Start query for active listings
        $query = MarketplaceListing::with('user')
            ->where('is_active', 1)
            ->where('created_at', '>=', $cutoffDate);

        // Step 3: Filter by category
        if (!is_null($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Step 4: Filter by search term
        if ($request->filled('search')) {
            $search = trim($request->search);
            Log::info('Searching for:', ['term' => $search]);

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%'])
                ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        // Step 5: Fetch results
        $listings = $query->latest()->get();

        Log::info('Marketplace Listing Results:', [
            'count' => $listings->count(),
            'titles' => $listings->pluck('title')
        ]);

        // Step 6: Transform image URLs
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
            $submittedImages = $request->images;

            $existingUrls = array_filter($submittedImages, function ($img) {
                return Str::startsWith($img, 'http');
            });

            $newBase64 = array_filter($submittedImages, function ($img) {
                return !Str::startsWith($img, 'http');
            });

            $uploadedImages = $this->handleBase64Images(array_values($newBase64));

            $listing->images = array_merge(
                $this->stripBaseUrls($existingUrls),
                $uploadedImages
            );

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

    private function stripBaseUrls(array $urls): array
    {
        return array_map(function ($url) {
            // This removes the domain + /uploads/ and returns just the filename
            return str_replace(asset('uploads/') . '/', '', $url);
        }, $urls);
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

    public function myListings(Request $request)
    {
        // Step 1: Get the date 14 days ago in Mexico timezone
        $cutoffDate = Carbon::now('America/Mexico_City')->subDays(14)->startOfDay();

        // Step 2: Update listings older than 14 days (only once before querying)
        MarketplaceListing::where('created_at', '<', $cutoffDate)
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

        // Step 3: Continue with user-specific listing query
        $query = MarketplaceListing::where('user_id', Auth::id());

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
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

    protected function handleBase64Images(array $images): array
    {
        $storedImages = [];

        // Ensure the uploads directory exists
        $uploadPath = public_path('uploads');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        foreach ($images as $index => $base64Image) {
            if (empty($base64Image)) continue;

            // Add default prefix if missing
            if (!Str::startsWith($base64Image, 'data:image')) {
                $base64Image = 'data:image/jpeg;base64,' . $base64Image;
            }

            if (!Str::contains($base64Image, ',')) continue;

            [$type, $data] = explode(',', $base64Image);
            $imageExt = explode('/', explode(';', $type)[0])[1] ?? 'jpg';
            $imageData = base64_decode($data);

            if (!$imageData) continue;

            $imageName = 'image_' . time() . "_$index.$imageExt";
            $imagePath = $uploadPath . '/' . $imageName;

            file_put_contents($imagePath, $imageData);

            // Set proper permissions to avoid 403
            chmod($imagePath, 0644);

            $storedImages[] = $imageName;
        }

        return $storedImages;
    }

    private function fullImageUrls($images)
    {
        $decoded = is_array($images) ? $images : json_decode($images, true);
        if (!$decoded || !is_array($decoded)) return [];

        return array_map(function ($path) {
            return asset('uploads/' . ltrim($path, '/'));
        }, $decoded);
    }
}