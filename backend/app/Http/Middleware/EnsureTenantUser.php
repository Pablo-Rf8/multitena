<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(in_array($request->user()?->role, ['company_admin', 'branch_admin', 'employee'], true)
            && $request->user()?->company_id && $request->user()?->branch_id, 403);

        return $next($request);
    }
}
