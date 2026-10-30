<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrmPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        abort_unless($user && (in_array($user->role, ['company_admin', 'branch_admin'], true)
            || ($user->role === 'employee' && $user->category?->is_active && in_array($permission, $user->category?->permissions ?? [], true))), 403);

        return $next($request);
    }
}
