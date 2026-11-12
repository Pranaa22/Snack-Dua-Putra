<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Sesi login berakhir.'], 401)
                : redirect()->route('admin.login');
        }
        abort_unless((bool) auth()->user()->is_admin, 403);
        return $next($request);
    }
}
