<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle($request, Closure $next, string $module)
    {
        $user = auth()->user();

        if (!$user) abort(403);

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->role === 'user') {
            abort(403, 'Unauthorized');
        }

        if ($user->hasPermission($module)) {
            return $next($request);
        }

        abort(403, 'Unauthorized');
    }
}
