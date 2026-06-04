<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminId = $request->session()->get('admin_id');
        $admin = $adminId ? User::where('role', 'admin')->find($adminId) : null;

        if (! $admin) {
            $request->session()->forget(['admin_id', 'admin_name']);

            return redirect()->route('login');
        }

        return $next($request);
    }
}
