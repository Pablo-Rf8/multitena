<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_if($user->role !== 'super_admin' && (! $user->company_id || ! $user->branch_id), 403);
        $allowed = $user?->is_active && ($user->role !== 'employee' || $user->approval_status === 'approved') && ($user->role === 'super_admin' || (
            $user->company?->is_active && $user->branch?->is_active
        ));

        if (! $allowed) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Tu acceso está suspendido. Contactá al administrador.']);
        }

        return $next($request);
    }
}
