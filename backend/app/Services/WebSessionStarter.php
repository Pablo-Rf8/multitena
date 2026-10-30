<?php

namespace App\Services;

use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Http\Request;

class WebSessionStarter
{
    public function start(Request $request, User $user, string $method = 'password'): MobileSession
    {
        $request->session()->regenerate();
        $request->session()->forget(['login_mode', 'login_company_id']);
        $session = MobileSession::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'channel' => 'web',
            'auth_method' => $method,
            'web_session_hash' => hash_hmac('sha256', $request->session()->getId().':'.$user->id, config('app.key')),
            'started_at' => now(),
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime')),
        ]);
        $request->session()->put('access_session_id', $session->id);
        $user->forceFill(['last_login_at' => now()])->save();

        return $session;
    }
}
