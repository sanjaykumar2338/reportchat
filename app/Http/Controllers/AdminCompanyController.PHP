<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Storage;

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
        //echo "<pre>"; print_r($users); die;

        // Firebase setup from JSON file
        $firebasePath = storage_path('app/firebase/firebase-credentials.json'); // make sure this path exists
        $factory = (new Factory)->withServiceAccount($firebasePath);
        $messaging = $factory->createMessaging();

        foreach ($users as $user) {
            // Save to DB
            \DB::table('notifications')->insert([
                'user_id' => $user->id,
                'company_id' => $user->company,
                'title' => $request->title,
                'message' => $request->message,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            
            // Send FCM push if FCM token exists
            //$face_token = 'fFuFCWbPQheST_8rpDB6DJ:APA91bGGBrrAdilC0ICT09d8Dru9WUG03kad-Z2dm7SgONcnzRbYRc3tJl_DYMbH0KowB99SjacpK8epHTD9rLgie6j_bQX6x226VgO-nQODmVbwGaLxvJg';

            if ($user->face_token) {
                try {
                    $message = CloudMessage::withTarget('token', $user->face_token)
                        ->withNotification(Notification::create($request->title, $request->message));

                    $response = $messaging->send($message);
                    \Log::info('FCM Response:', ['response' => $response]);

                    \Log::error("FCM send.");

                } catch (\Exception $e) {
                    \Log::error("FCM send failed for user {$user->id}: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification sent to selected companies.',
        ]);
    }
}