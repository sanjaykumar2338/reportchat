<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceListing;
use App\Models\MarketplaceCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

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
            'user_id'     => 'required|exists:users,id',
            'category_id' => 'required|exists:marketplace_categories,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric|min:0',
            'whatsapp'    => 'required|string|max:15',
            'is_active'   => 'nullable|boolean',
            'published_at'=> 'nullable|date',
            'ends_at'     => 'nullable|date|after_or_equal:published_at',
            'images.*'    => 'nullable|image|max:4096',
        ]);

        // upload images
        $paths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $paths[] = $file->store('marketplace', 'public');
            }
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['images']    = $paths; // model casts to array->JSON

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
            'user_id'     => 'required|exists:users,id',
            'category_id' => 'required|exists:marketplace_categories,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric|min:0',
            'whatsapp'    => 'required|string|max:15',
            'is_active'   => 'nullable|boolean',
            'published_at'=> 'nullable|date',
            'ends_at'     => 'nullable|date|after_or_equal:published_at',
            'images.*'    => 'nullable|image|max:4096',
            'remove_images'=> 'array',
            'remove_images.*'=> 'string',
        ]);

        // merge current + new uploads
        $current = Arr::wrap($listing->images);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $current[] = $file->store('marketplace', 'public');
            }
        }

        // remove selected images
        $toRemove = Arr::wrap($request->input('remove_images', []));
        if ($toRemove) {
            foreach ($toRemove as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            $current = array_values(array_diff($current, $toRemove));
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['images']    = $current;

        $listing->update($data);

        return redirect()->route('admin.marketplace.index')
            ->with('success','Anuncio actualizado correctamente.');
    }

    public function destroy($id)
    {
        $listing = MarketplaceListing::findOrFail($id);

        // (Optional) delete files
        foreach (Arr::wrap($listing->images) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $listing->delete();
        return redirect()->route('admin.marketplace.index')
            ->with('success','Anuncio eliminado correctamente.');
    }
}