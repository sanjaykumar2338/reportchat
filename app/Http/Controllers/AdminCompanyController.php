<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Storage;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

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

        // 🔐 Step 1: Generate access token
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $serviceAccountPath = storage_path('app/firebase/firebase-credentials.json');

        $credentials = new ServiceAccountCredentials($scopes, $serviceAccountPath);
        $tokenData = $credentials->fetchAuthToken();

        if (!isset($tokenData['access_token'])) {
            return response()->json(['status' => 'error', 'message' => 'FCM access token failed'], 500);
        }

        $accessToken = $tokenData['access_token'];
        $projectId = json_decode(file_get_contents($serviceAccountPath), true)['project_id'];

        // 🔁 Step 2: Loop through users and send notification
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

            if ($user->face_token) {
                try {
                    $notificationPayload = [
                        'message' => [
                            'token' => $user->face_token,
                            'notification' => [
                                'title' => $request->title,
                                'body' => $request->message,
                            ]
                        ]
                    ];

                    $response = Http::withToken($accessToken)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                        ])
                        ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $notificationPayload);

                    \Log::info('FCM Raw Response', ['user_id' => $user->id, 'res' => $response->body()]);
                } catch (\Exception $e) {
                    \Log::error("FCM send failed for user {$user->id}: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications sent using access token.',
        ]);
    }
}