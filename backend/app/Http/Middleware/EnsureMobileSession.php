<?php
namespace App\Http\Middleware;

use App\Models\MobileSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        if (! $token || ! method_exists($token, 'delete')) {
            return response()->json(['message' => 'Token requerido.'], 401);
        }
        $session = MobileSession::where('token_id', $token->id)->where('user_id', $user->id)->where('company_id', $user->company_id)->with('device')->first();
        if (! $session || $session->status !== 'active' || ! $session->device || $session->device->status !== 'approved' || ! $user->is_active || ! $user->company?->is_active || ! $user->branch?->is_active || ($user->role === 'employee' && ($user->approval_status !== 'approved' || ! \App\Models\CompanySetting::where('company_id', $user->company_id)->value('employee_access_enabled')))) {
            return response()->json(['message' => 'Acceso revocado.'], 403);
        }
        if ($session->expires_at->isPast()) {
            $session->update(['status' => 'expired', 'ended_at' => now(), 'end_reason' => 'expired']);
            $token->delete();
            return response()->json(['message' => 'Sesión expirada.'], 401);
        }
        $request->attributes->set('mobile_session', $session);
        return $next($request);
    }
}
