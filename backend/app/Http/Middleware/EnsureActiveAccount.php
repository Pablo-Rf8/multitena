<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MobileSession;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_if($user->role !== 'super_admin' && (! $user->company_id || ! $user->branch_id), 403);
        $allowed = $user?->is_active && ($user->role !== 'employee' || ($user->approval_status === 'approved' && \App\Models\CompanySetting::firstOrCreate(['company_id' => $user->company_id])->employee_access_enabled)) && ($user->role === 'super_admin' || (
            $user->company?->is_active && $user->branch?->is_active
        ));

        if (! $allowed) {
            MobileSession::where('id', $request->session()->get('access_session_id'))->where('status', 'active')->update(['status' => 'revoked', 'ended_at' => now(), 'end_reason' => 'account_disabled']);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Tu acceso está suspendido. Contactá al administrador.']);
        }

        $hash = hash_hmac('sha256', $request->session()->getId().':'.$user->id, config('app.key'));
        $session = $request->session()->has('access_session_id')
            ? MobileSession::find($request->session()->get('access_session_id'))
            : MobileSession::where('web_session_hash', $hash)->first();
        if ($session && $session->user_id !== $user->id) {
            $session = MobileSession::where('web_session_hash', $hash)->first();
            $request->session()->forget('access_session_id');
        }
        if ($session && $session->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'La sesión fue finalizada.']);
        }
        if (! $session) {
            $session = MobileSession::create(['company_id' => $user->company_id, 'user_id' => $user->id, 'channel' => 'web', 'web_session_hash' => $hash, 'started_at' => now(), 'last_seen_at' => now(), 'expires_at' => now()->addMinutes(config('session.lifetime'))]);
        } else {
            $session->update(['last_seen_at' => now(), 'expires_at' => now()->addMinutes(config('session.lifetime'))]);
        }
        $request->session()->put('access_session_id', $session->id);

        return $next($request);
    }
}
