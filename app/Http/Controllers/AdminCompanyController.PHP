<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query();

        // Apply filters if needed
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $companies = $query->latest()->paginate(10);

        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Company::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.companies.index')->with('success', 'Company created successfully.');
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $company = Company::findOrFail($id);
        $company->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.companies.index')->with('success', 'Company updated successfully.');
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return redirect()->route('admin.companies.index')->with('success', 'Company deleted successfully.');
    }

    
    
    public function sendNotification(Request $request)
    {
        $request->validate([
            'companies' => 'required',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $companyIds = is_array($request->companies)
            ? $request->companies
            : explode(',', $request->companies);

        $users = \App\Models\User::whereIn('company', $companyIds)->get();

        foreach ($users as $user) {
            \DB::table('notifications')->insert([
                'user_id' => $user->id,
                'company_id' => $user->company,
                'title' => $request->title,
                'message' => $request->message,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification sent to selected companies.',
        ]);
    }
}