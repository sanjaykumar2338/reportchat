<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chat;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->where('role', '!=', 'superadmin'); // hide superadmins

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }

        if ($request->filled('username')) {
            $query->where('username', 'like', '%'.$request->username.'%');
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role); // filter by user/admin
        }

        $users = $query->latest()->paginate(10);
        $allEnabled = User::where('role', 'admin')->where('whatsapp_notifications', false)->doesntExist();

        return view('admin.users.index', compact('users','allEnabled'));
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    public function dashboard()
    {
        $totalChats     = Chat::count();
        $totalUsers     = User::count();
        $activeSessions = User::whereNotNull('last_login_at')->count();

        return view('admin.dashboard', compact('totalChats', 'totalUsers', 'activeSessions'));
    }

    public function create()
    {
        $companies = Company::all();
        return view('admin.users.edit', compact('companies'));
    }

    public function store(Request $request)
    {
        // 1) Validate
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string|max:20',
            'role'     => 'nullable|in:user,admin,superadmin',
            'company'  => 'nullable|exists:companies,id',
            'permissions'        => 'nullable|array',
            'report_categories'  => 'nullable|array', // e.g. ['mantenimiento','seguridad',...]
        ]);

        // 2) Base fields
        $data = [
            'name'       => trim($validated['name']),
            'username'   => trim($validated['username']),
            'email'      => strtolower(trim($validated['email'])),
            'password'   => Hash::make($validated['password']),
            'phone'      => $validated['phone'] ?? null,
            'role'       => $validated['role'] ?? 'user',
            'company_id' => $validated['company'] ?? null,
        ];

        // 3) If the editor is only ADMIN, restrict to creating plain "user"
        if (auth()->user()->isAdmin() && !auth()->user()->isSuperAdmin()) {
            $data['role'] = 'user';
            $data['permissions'] = [];
            $data['report_categories'] = [];
        } else {
            // 4) Superadmin can set permissions
            $permissionsInput = $request->input('permissions', []);
            // Normalize to boolean flags: ['dashboard'=>true, ...]
            $permissions = collect($permissionsInput)
                ->map(fn ($v) => (bool)$v)
                ->toArray();

            // Only persist permissions when creating an admin
            if (($data['role'] ?? 'user') === 'admin') {
                $data['permissions'] = $permissions;

                // Report categories only if "reports" permission is enabled
                $reportCats = $request->input('report_categories', []);
                $reportCats = array_values(array_unique(array_filter((array)$reportCats)));

                $data['report_categories'] = !empty($permissions['reports']) ? $reportCats : [];
            } else {
                $data['permissions'] = [];
                $data['report_categories'] = [];
            }
        }

        // 5) Create user
        User::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit($id)
    {
        $user      = User::findOrFail($id);
        $companies = Company::all();
        return view('admin.users.edit', compact('user', 'companies'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 1) Validate
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role'     => 'nullable|in:user,admin,superadmin',
            'phone'    => 'nullable|string|max:20',
            'company'  => 'nullable|exists:companies,id',
            'permissions'        => 'nullable|array',
            'report_categories'  => 'nullable|array',
        ]);

        // 2) Base updatable fields
        $data = [
            'name'       => trim($validated['name']),
            'username'   => trim($validated['username']),
            'email'      => strtolower(trim($validated['email'])),
            'phone'      => $validated['phone'] ?? null,
            'company_id' => $validated['company'] ?? null,
        ];

        // Password (only if provided)
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        // Desired role (falls back to current)
        $desiredRole = $validated['role'] ?? $user->role;

        // 3) If the editor is only ADMIN, lock target as plain user (no perms/categories)
        if (auth()->user()->isAdmin() && !auth()->user()->isSuperAdmin()) {
            $data['role'] = 'user';
            $data['permissions'] = [];
            $data['report_categories'] = [];
        } else {
            // 4) Superadmin: can set role, permissions, and categories
            $data['role'] = $desiredRole;

            // Normalize permissions to boolean flags
            $permissionsInput = $request->input('permissions', []);
            $permissions = collect($permissionsInput)
                ->map(fn($v) => (bool)$v)
                ->toArray();

            if ($data['role'] === 'admin') {
                $data['permissions'] = $permissions;

                // Only keep report categories if "reports" permission is enabled
                $reportCats = $request->input('report_categories', []);
                $reportCats = array_values(array_unique(array_filter((array)$reportCats)));
                $data['report_categories'] = !empty($permissions['reports']) ? $reportCats : [];
            } else {
                // Not an admin → clear perms & categories
                $data['permissions'] = [];
                $data['report_categories'] = [];
            }
        }

        // 5) Update
        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if($user->username=='mario2025'){
            return redirect()->route('admin.users.index')->with('error', 'No se puede eliminar el usuario predeterminado.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function reservationHistory($userId)
    {
        $user = User::with('reservations.room')->findOrFail($userId);
        return view('admin.users.reservation_history', compact('user'));
    }

    public function profile()
    {
        $user = auth()->user();
        $companies = \App\Models\Company::all(); // optional if you want company select
        return view('admin.users.profile', compact('user', 'companies'));
    }

    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'company'  => 'nullable|exists:companies,id',
        ]);

        // normalize
        $validated['email']    = strtolower(trim($validated['email']));
        $validated['username'] = trim($validated['username']);
        $validated['whatsapp_notifications'] = $request->has('whatsapp_notifications');

        // map company select to company_id (optional)
        $validated['company_id'] = $validated['company'] ?? $user->company_id ?? null;

        // handle password only if provided
        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        unset($validated['company']); // not a column

        $user->update($validated);

        return redirect()->route('admin.profile')->with('success', 'Perfil actualizado correctamente.');
    }

    public function showUploadForm()
    {
        return view('admin.users.upload_csv');
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path = $request->file('csv')->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->withErrors(['csv' => 'No se pudo leer el archivo CSV.']);
        }

        // 1) Cabeceras requeridas (ES)
        $expected = ['Nombre','Usuario','Correo','Telefono','Contraseña','Empresa'];

        // 2) Leer cabecera
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv' => 'El archivo CSV está vacío.']);
        }

        // Quitar BOM posible en la primera celda
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        // 3) Normalización: minúsculas, sin acentos, sin espacios extra
        $normalize = function ($s) {
            $s = trim((string)$s);
            $s = Str::of($s)->lower()->value();
            $s = strtr($s, [
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
                'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u'
            ]);
            return $s;
        };

        $normHeader    = array_map($normalize, $header);
        $normExpected  = array_map($normalize, $expected);

        // 4) Construir mapa de índices (y fallar si falta alguna)
        $map = [];
        foreach ($normExpected as $i => $colNorm) {
            $pos = array_search($colNorm, $normHeader, true);
            if ($pos === false) {
                fclose($handle);
                return back()->withErrors(['csv' => "Falta la columna requerida: {$expected[$i]}"]);
            }
            $map[$expected[$i]] = $pos;
        }

        // 5) Procesar filas
        $created = 0; $skipped = 0; $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            // Saltar líneas vacías
            if (count($row) === 1 && trim($row[0]) === '') continue;

            $Nombre      = trim($row[$map['Nombre']]      ?? '');
            $Usuario     = trim($row[$map['Usuario']]     ?? '');
            $Correo      = trim($row[$map['Correo']]      ?? '');
            $Telefono    = trim($row[$map['Telefono']]    ?? '');
            $Contrasena  = trim($row[$map['Contraseña']]  ?? '');
            $EmpresaName = trim($row[$map['Empresa']]     ?? '');

            if ($Nombre === '' || $Usuario === '' || $Correo === '' || $Contrasena === '') {
                $skipped++; $errors[] = "Fila omitida ({$Correo}): campos requeridos vacíos.";
                continue;
            }

            $company = null;
            if ($EmpresaName !== '') {
                $company = Company::where('name', $EmpresaName)->first();
            }

            // Solo rol 'user'
            $data = [
                'name'       => $Nombre,
                'username'   => $Usuario,
                'email'      => strtolower($Correo),
                'phone'      => $Telefono ?: null,
                'password'   => Hash::make($Contrasena),
                'role'       => 'user',
                'company_id' => $company->id ?? null,
                'permissions'       => [],
                'report_categories' => [],
            ];

            $exists = User::where('email', $data['email'])
                        ->orWhere('username', $data['username'])
                        ->first();

            if ($exists) {
                $skipped++;
                $errors[] = "Omitido (ya existe): {$data['email']} / {$data['username']}";
                continue;
            }

            try {
                User::create($data);
                $created++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Error creando {$data['email']}: ".$e->getMessage();
            }
        }

        fclose($handle);

        $msg = "Importación completada. Creados: {$created}, Omitidos: {$skipped}";
        return redirect()->route('admin.users.index')->with([
            'success'    => $msg,
            'csv_errors' => $errors,
        ]);
    }

    public function toggleAllNotifications()
    {
        // Check if there are any admins with notifications turned off.
        $anyDisabled = User::where('role', 'admin')->where('whatsapp_notifications', false)->exists();

        if ($anyDisabled) {
            // If at least one is disabled, enable all.
            User::where('role', 'admin')->update(['whatsapp_notifications' => true]);
            return back()->with('success', 'Notificaciones habilitadas para todos los administradores.');
        } else {
            // Otherwise, disable all.
            User::where('role', 'admin')->update(['whatsapp_notifications' => false]);
            return back()->with('success', 'Notificaciones deshabilitadas para todos los administradores.');
        }
    }

    /**
     * Toggles notifications for a single user.
     */
    public function toggleNotification(User $user)
    {
        // Toggle the boolean value and save it
        $user->whatsapp_notifications = !$user->whatsapp_notifications;
        $user->save();

        $status = $user->whatsapp_notifications ? 'habilitadas' : 'deshabilitadas';
        
        return back()->with('success', "Notificaciones {$status} para el usuario {$user->name}.");
    }

    // Note: The enableAllNotifications method is now redundant because
    // toggleAllNotifications handles both cases. You can keep it or remove it.
    public function enableAllNotifications()
    {
        User::where('role', 'admin')->update(['whatsapp_notifications' => true]);
        return back()->with('success', 'Notificaciones habilitadas para todos los administradores');
    }
}
