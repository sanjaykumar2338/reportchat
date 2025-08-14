<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceListing;
use App\Models\MarketplaceCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminMarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $q = MarketplaceListing::with(['category','user']);

        if ($request->filled('q')) {
            $term = $request->q;
            $q->where(function($w) use ($term) {
                $w->where('title','like','%'.$term.'%')
                  ->orWhere('description','like','%'.$term.'%')
                  ->orWhere('whatsapp','like','%'.$term.'%');
            });
        }
        if ($request->filled('category_id')) $q->where('category_id', $request->category_id);
        if ($request->filled('user_id'))     $q->where('user_id', $request->user_id);
        if ($request->filled('active'))      $q->where('is_active', (int)$request->active);

        $listings   = $q->latest()->paginate(10)->withQueryString();
        $categories = MarketplaceCategory::orderBy('name')->get();
        $users      = User::orderBy('name')->get();

        return view('admin.marketplace.index', compact('listings','categories','users'));
    }

    public function create()
    {
        $categories = MarketplaceCategory::orderBy('name')->get();
        $users      = User::orderBy('name')->get();
        return view('admin.marketplace.form', compact('categories','users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'category_id'   => 'required|exists:marketplace_categories,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'nullable|numeric|min:0',
            'whatsapp'      => 'required|string|max:20',
            'is_active'     => 'nullable|boolean',
            'published_at'  => 'nullable|date',
            'ends_at'       => 'nullable|date|after_or_equal:published_at',

            // uploads + base64 (either or both)
            'images'   => 'nullable|array',
            'images.*' => 'nullable|file|image|max:16384',
            'images_base64' => 'nullable|string', // textarea (lines or JSON)
        ]);

        // collect filenames from uploads and/or base64
        $filenames = array_merge(
            $this->saveUploadedFiles($request),
            $this->handleBase64Images($this->parseBase64Textarea($request->input('images_base64')))
        );

        $data['is_active']    = $request->boolean('is_active');
        $data['published_at'] = $data['published_at'] ?? now();
        if ($data['is_active'] && empty($data['ends_at'])) {
            $data['ends_at'] = now()->addDays(14);
        }
        $data['images'] = $filenames;

        MarketplaceListing::create($data);

        return redirect()->route('admin.marketplace.index')
            ->with('success','Anuncio creado correctamente.');
    }

    public function edit($id)
    {
        $listing    = MarketplaceListing::findOrFail($id);
        $categories = MarketplaceCategory::orderBy('name')->get();
        $users      = User::orderBy('name')->get();

        return view('admin.marketplace.form', compact('listing','categories','users'));
    }

    public function update(Request $request, $id)
    {
        $listing = MarketplaceListing::findOrFail($id);

        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'category_id'   => 'required|exists:marketplace_categories,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'nullable|numeric|min:0',
            'whatsapp'      => 'required|string|max:20',
            'is_active'     => 'nullable|boolean',
            'published_at'  => 'nullable|date',
            'ends_at'       => 'nullable|date|after_or_equal:published_at',

            'images'   => 'nullable|array',
            'images.*' => 'nullable|file|image|max:16384',
            'images_base64' => 'nullable|string',

            'remove_images'   => 'array',
            'remove_images.*' => 'string',
        ]);

        // start with current filenames (DB stores only names like "image_xxx.jpg")
        $current = Arr::wrap($listing->images);

        // add new files (uploads + base64)
        $added = array_merge(
            $this->saveUploadedFiles($request),
            $this->handleBase64Images($this->parseBase64Textarea($request->input('images_base64')))
        );
        $merged = array_values(array_unique(array_merge($current, $added)));

        // remove selected filenames (and unlink from /public/uploads)
        $toRemove = Arr::wrap($request->input('remove_images', []));
        if ($toRemove) {
            foreach ($toRemove as $name) {
                $full = public_path('uploads/'.basename($name));
                if (is_file($full)) @unlink($full);
            }
            $merged = array_values(array_diff($merged, $toRemove));
        }

        $data['is_active']    = $request->boolean('is_active');
        $data['published_at'] = $data['published_at'] ?? $listing->published_at ?? now();
        if ($data['is_active'] && empty($data['ends_at'])) {
            $data['ends_at'] = now()->addDays(14);
        }
        $data['images'] = $merged;

        $listing->update($data);

        return redirect()->route('admin.marketplace.index')
            ->with('success','Anuncio actualizado correctamente.');
    }

    public function destroy($id)
    {
        $listing = MarketplaceListing::findOrFail($id);

        foreach (Arr::wrap($listing->images) as $name) {
            $full = public_path('uploads/'.basename($name));
            if (is_file($full)) @unlink($full);
        }

        $listing->delete();
        return redirect()->route('admin.marketplace.index')
            ->with('success','Anuncio eliminado correctamente.');
    }

    /* ----------------- helpers (match mobile API behavior) ----------------- */

    /**
     * Save browser uploads to /public/uploads and return array of filenames.
     */
    protected function saveUploadedFiles(Request $request): array
    {
        $out = [];
        if (!$request->hasFile('images')) return $out;

        $uploadPath = public_path('uploads');
        if (!is_dir($uploadPath)) @mkdir($uploadPath, 0755, true);

        foreach ($request->file('images') as $i => $file) {
            if ($file && $file->isValid()) {
                $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $name = 'image_'.time().'_'.$i.'.'.$ext;
                $file->move($uploadPath, $name);
                @chmod($uploadPath.'/'.$name, 0644);
                $out[] = $name; // store only filename (same as mobile)
            }
        }
        return $out;
    }

    /**
     * Accept textarea input (JSON array or one base64 per line) and return array of strings.
     */
    protected function parseBase64Textarea(?string $raw): array
    {
        if (!$raw) return [];
        $raw = trim($raw);
        if ($raw === '') return [];

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map('trim', $decoded)));
        }
        // fallback: split by lines
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));
    }

    /**
     * Same behavior as your mobile API: accept base64 strings (with or without data:image/...;base64,)
     * write files into /public/uploads, and return filenames.
     */
    protected function handleBase64Images(array $images): array
    {
        $stored = [];
        if (empty($images)) return $stored;

        $uploadPath = public_path('uploads');
        if (!is_dir($uploadPath)) @mkdir($uploadPath, 0755, true);

        foreach ($images as $index => $base64Image) {
            if (empty($base64Image)) continue;

            if (!Str::startsWith($base64Image, 'data:image')) {
                $base64Image = 'data:image/jpeg;base64,' . $base64Image;
            }
            if (!Str::contains($base64Image, ',')) continue;

            [$type, $data] = explode(',', $base64Image, 2);
            $imageExt = explode('/', explode(';', $type)[0])[1] ?? 'jpg';
            $imageData = base64_decode($data);

            if (!$imageData) continue;

            $name = 'image_'.time().'_'.$index.'.'.$imageExt;
            $path = $uploadPath.'/'.$name;

            file_put_contents($path, $imageData);
            @chmod($path, 0644);

            $stored[] = $name; // store only filename
        }

        return $stored;
    }
}