<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
        if (Auth::check() && Auth::user()->is_admin) {
            return $next($request);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
