<?php

namespace App\Http\Controllers;

use App\Models\CodeAuthAttempt;
use App\Models\CodeCredential;
use App\Models\CodeLink;
use App\Models\Company;
use App\Models\User;
use App\Services\TemporaryCodes;
use App\Services\UserAccess;
use App\Services\WebSessionStarter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class CodeLoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $company = $this->company($request);
        if (! $company) return redirect()->route('login')->withErrors(['company_code' => 'Elegí una empresa para continuar.']);
        return view('auth.code-login', compact('company'));
    }

    public function login(Request $request, TemporaryCodes $codes, UserAccess $access, WebSessionStarter $sessions): RedirectResponse
    {
        $company = $this->company($request);
        $email = strtolower(trim((string) $request->input('email')));
        $code = trim((string) $request->input('code'));
        $user = $company && filter_var($email, FILTER_VALIDATE_EMAIL)
            ? User::with(['company', 'branch'])->where('company_id', $company->id)->where('email', $email)->where('role', '!=', 'super_admin')->first()
            : null;
        $key = 'code-login:'.hash('sha256', ($company?->id ?? 0).'|'.$email.'|'.$request->ip());
        $ipKey = 'code-login-ip:'.hash('sha256', (string) $request->ip());

        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 100)) {
            return $this->reject($request, $user, $company, 'rate_limited', $key, $ipKey, false);
        }
        if (! $company || ! $user || ! preg_match('/^\d{6}$/D', $code)) {
            return $this->reject($request, $user, $company, 'invalid_identity_or_format', $key, $ipKey);
        }
        if (! $access->allowed($user)) return $this->reject($request, $user, $company, 'account_disabled', $key, $ipKey);

        $result = DB::transaction(function () use ($request, $user, $company, $code, $codes, $sessions) {
            $link = CodeLink::where('user_id', $user->id)->latest('id')->lockForUpdate()->first();
            if (! $link || $link->company_id !== $company->id || $link->branch_id !== $user->branch_id) return 'link_missing';
            if (! $link->isUsable()) return $link->status === 'active' ? 'link_expired' : 'link_'.$link->status;

            $credential = CodeCredential::where('user_id', $user->id)->lockForUpdate()->first();
            $verified = $credential ? $codes->verifyAndConsume($credential, $code) : 'incorrect';
            if ($verified !== 'valid') return $verified;

            Auth::login($user);
            $session = $sessions->start($request, $user, 'totp');
            $this->record($request, $user, $company, 'success', null, $session->web_session_hash);
            return 'valid';
        }, 3);
        if ($result !== 'valid') return $this->reject($request, $user, $company, $result, $key, $ipKey);

        RateLimiter::clear($key);

        return redirect()->route('dashboard');
    }

    private function reject(Request $request, ?User $user, ?Company $company, string $reason, string $key, string $ipKey, bool $hit = true): RedirectResponse
    {
        if ($hit) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
        }
        $this->record($request, $user, $company, 'failed', $reason);
        return back()->withErrors(['code' => 'No se pudo iniciar sesión con ese código. Verificá tus datos o solicitá un enlace nuevo.'])->onlyInput('email');
    }

    private function record(Request $request, ?User $user, ?Company $company, string $result, ?string $reason, ?string $sessionHash = null): void
    {
        CodeAuthAttempt::create([
            'user_id' => $user?->id,
            'company_id' => $company?->id,
            'branch_id' => $user?->branch_id,
            'role' => $user?->role,
            'ip_address' => $request->ip(),
            'result' => $result,
            'reason' => $reason,
            'session_hash' => $sessionHash,
            'created_at' => now(),
        ]);
    }

    private function company(Request $request): ?Company
    {
        return $request->session()->get('login_mode') === 'company'
            ? Company::where('is_active', true)->find($request->session()->get('login_company_id'))
            : null;
    }
}
