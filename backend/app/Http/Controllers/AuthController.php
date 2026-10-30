<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\MobileSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(Request $request): View
    {
        $company = $request->session()->get('login_mode') === 'company'
            ? Company::where('is_active', true)->find($request->session()->get('login_company_id'))
            : null;

        return view('auth.login', [
            'loginCompany' => $company,
            'platformLogin' => $request->session()->get('login_mode') === 'platform',
            'loginLogo' => $company?->logoUrl(public: true),
        ]);
    }

    public function selectCompany(Request $request): RedirectResponse
    {
        $data = $request->validate(['company_code' => ['required', 'string', 'max:32']]);
        $company = Company::where('code', strtoupper(trim($data['company_code'])))->where('is_active', true)->first();
        if (! $company) {
            return back()->withErrors(['company_code' => 'Código de empresa no reconocido.'])->onlyInput('company_code');
        }
        $request->session()->put(['login_mode' => 'company', 'login_company_id' => $company->id]);

        return redirect()->route('login');
    }

    public function selectPlatform(Request $request): RedirectResponse
    {
        $request->session()->put(['login_mode' => 'platform']);
        $request->session()->forget('login_company_id');

        return redirect()->route('login');
    }

    public function changeContext(Request $request): RedirectResponse
    {
        $request->session()->forget(['login_mode', 'login_company_id']);

        return redirect()->route('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $mode = $request->session()->get('login_mode');
        $companyId = $request->session()->get('login_company_id');
        $company = $mode === 'company' ? Company::where('is_active', true)->find($companyId) : null;
        if ($mode !== 'platform' && ! $company) {
            return redirect()->route('login')->withErrors(['company_code' => 'Elegí una empresa para continuar.']);
        }
        $credentials['email'] = strtolower(trim($credentials['email']));
        $credentials[$mode === 'platform' ? 'role' : 'company_id'] = $mode === 'platform' ? 'super_admin' : $company->id;

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->onlyInput('email');
        }

        $user = $request->user();
        if (! $user->is_active || ($user->role === 'employee' && ($user->approval_status !== 'approved' || ! CompanySetting::firstOrCreate(['company_id' => $user->company_id])->employee_access_enabled)) || ($user->role !== 'super_admin' && (! $user->company?->is_active || ! $user->branch?->is_active))) {
            Auth::logout();

            return back()->withErrors(['email' => 'Tu acceso está suspendido. Contactá al administrador.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->forget(['login_mode', 'login_company_id']);
        $accessSession = MobileSession::create(['company_id' => $user->company_id, 'user_id' => $user->id, 'channel' => 'web', 'web_session_hash' => hash_hmac('sha256', $request->session()->getId().':'.$user->id, config('app.key')), 'started_at' => now(), 'last_seen_at' => now(), 'expires_at' => now()->addMinutes(config('session.lifetime'))]);
        $request->session()->put('access_session_id', $accessSession->id);
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $companyId = $request->user()?->company_id;
        MobileSession::where('id', $request->session()->get('access_session_id'))->where('status', 'active')->update(['status' => 'closed', 'ended_at' => now(), 'end_reason' => 'logout']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put(['login_mode' => $companyId ? 'company' : 'platform']);
        if ($companyId) $request->session()->put('login_company_id', $companyId);

        return redirect()->route('login');
    }
}
