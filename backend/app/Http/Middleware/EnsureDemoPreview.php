<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDemoPreview
{
    public function handle(Request $request, Closure $next): Response
    {
        $preview = $request->session()->get('demo_preview');
        if ($preview !== null) {
            $user = $request->user();
            $valid = is_array($preview)
                && $user?->role === 'company_admin'
                && $user->id === ($preview['demo_user_id'] ?? null)
                && $user->company_id === ($preview['company_id'] ?? null)
                && $user->company?->is_demo
                && User::whereKey($preview['super_admin_id'] ?? 0)->where('role', 'super_admin')->where('is_active', true)->exists();

            if (! $valid) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
