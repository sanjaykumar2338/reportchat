<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MarketplaceCategory;

class AdminMarketplaceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = MarketplaceCategory::query();

        if ($request->filled('name')) {
            $q->where('name', 'like', '%'.$request->name.'%');
        }
        if ($request->filled('icon')) {
            $q->where('icon', 'like', '%'.$request->icon.'%');
        }

        $categories = $q->latest()->paginate(10)->withQueryString();
        return view('admin.marketplace_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.marketplace_categories.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255', // store filename or path
        ]);

        MarketplaceCategory::create($data);
        return redirect()->route('admin.marketplace_categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit($id)
    {
        $category = MarketplaceCategory::findOrFail($id);
        return view('admin.marketplace_categories.form', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = MarketplaceCategory::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $category->update($data);
        return redirect()->route('admin.marketplace_categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy($id)
    {
        $category = MarketplaceCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.marketplace_categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}