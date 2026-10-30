<?php
namespace App\Http\Controllers;

use App\Models\{CodeAuthAttempt, CodeCredential, CodeLink, Company, CompanySetting, Device, MobileSession, User};
use App\Services\AccessNotice;
use App\Services\TemporaryCodes;
use App\Services\UserAccess;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'company_code' => ['required', 'string'],
            'device_id' => ['required', 'string', 'min:8', 'max:255'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $user = User::where('email', strtolower($data['email']))->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->company || ! hash_equals((string) $user->company->code, $data['company_code']) || ! $user->is_active || ! $user->company->is_active || ! $user->branch?->is_active || $user->branch?->company_id !== $user->company_id || ($user->role === 'employee' && $user->approval_status !== 'approved')) {
            return response()->json(['message' => 'Credenciales o acceso inválidos.'], 401);
        }
        return $this->finishLogin($request, $user, $data);
    }

    public function loginWithCode(Request $request, TemporaryCodes $codes, UserAccess $access): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'regex:/^\d{6}$/D'],
            'company_code' => ['required', 'string', 'max:32'],
            'device_id' => ['required', 'string', 'min:8', 'max:255'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $company = Company::where('code', strtoupper(trim($data['company_code'])))->where('is_active', true)->first();
        $email = strtolower(trim($data['email']));
        $user = $company ? User::with(['company', 'branch'])->where('company_id', $company->id)->where('email', $email)->where('role', '!=', 'super_admin')->first() : null;
        $key = 'mobile-code:'.hash('sha256', ($company?->id ?? 0).'|'.$email.'|'.$request->ip());
        $ipKey = 'mobile-code-ip:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 100)) {
            return $this->rejectCode($request, $user, $company, 'rate_limited', $key, $ipKey, false);
        }
        if (! $user || ! $access->allowed($user)) {
            return $this->rejectCode($request, $user, $company, 'invalid_identity_or_account', $key, $ipKey);
        }

        $result = DB::transaction(function () use ($user, $company, $data, $codes) {
            $link = CodeLink::where('user_id', $user->id)->latest('id')->lockForUpdate()->first();
            if (! $link || $link->company_id !== $company->id || $link->branch_id !== $user->branch_id) return 'link_missing';
            if (! $link->isUsable()) return $link->status === 'active' ? 'link_expired' : 'link_'.$link->status;
            $credential = CodeCredential::where('user_id', $user->id)->lockForUpdate()->first();
            return $credential ? $codes->verifyAndConsume($credential, $data['code']) : 'incorrect';
        }, 3);
        if ($result !== 'valid') return $this->rejectCode($request, $user, $company, $result, $key, $ipKey);

        RateLimiter::clear($key);
        return $this->finishLogin($request, $user, $data, 'totp');
    }

    private function finishLogin(Request $request, User $user, array $data, string $method = 'password'): JsonResponse
    {
        $settings = CompanySetting::firstOrCreate(['company_id' => $user->company_id]);
        if ($user->role === 'employee' && ! $settings->employee_access_enabled) {
            return response()->json(['message' => 'Acceso de empleados deshabilitado.'], 403);
        }
        $device = Device::firstOrCreate(
            ['user_id' => $user->id, 'identifier_hash' => hash('sha256', $data['device_id'])],
            ['company_id' => $user->company_id, 'name' => $data['device_name'], 'status' => $settings->require_device_approval ? 'pending' : 'approved']
        );
        if ($device->company_id !== $user->company_id || $device->status !== 'approved') {
            if ($device->wasRecentlyCreated) {
                AccessNotice::admins($user->company_id, 'device_pending', $device->id, 'Dispositivo pendiente de aprobación');
            }
            if ($method === 'totp') $this->recordCode($request, $user, $user->company, 'failed', 'device_not_approved');
            return response()->json(['message' => 'Dispositivo pendiente de aprobación.'], 403);
        }
        $expires = now()->addHours($settings->token_lifetime_hours);
        $token = $user->createToken('mobile:'.$user->company_id.':'.$device->id, ['mobile'], $expires);
        $session = MobileSession::create([
            'company_id' => $user->company_id, 'user_id' => $user->id, 'device_id' => $device->id,
            'token_id' => $token->accessToken->id, 'channel' => 'mobile', 'auth_method' => $method, 'started_at' => now(), 'last_seen_at' => now(), 'expires_at' => $expires,
        ]);
        $device->update(['last_seen_at' => now()]);
        if ($method === 'totp') $this->recordCode($request, $user, $user->company, 'success', null, hash_hmac('sha256', 'mobile:'.$session->id.':'.$user->id, config('app.key')));
        return response()->json(['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $expires->toIso8601String(), 'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'company_id' => $user->company_id]]);
    }

    private function rejectCode(Request $request, ?User $user, ?Company $company, string $reason, string $key, string $ipKey, bool $hit = true): JsonResponse
    {
        if ($hit) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
        }
        $this->recordCode($request, $user, $company, 'failed', $reason);
        return response()->json(['message' => 'Código o acceso inválidos.'], 401);
    }

    private function recordCode(Request $request, ?User $user, ?Company $company, string $result, ?string $reason, ?string $sessionHash = null): void
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

    public function session(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->only(['id', 'name', 'email', 'role', 'company_id', 'branch_id']), 'session' => $request->attributes->get('mobile_session')]);
    }

    public function logout(Request $request): JsonResponse
    {
        $session = $request->attributes->get('mobile_session');
        $session->update(['status' => 'closed', 'ended_at' => now(), 'end_reason' => 'logout']);
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function registerPush(Request $request): JsonResponse
    {
        $data = $request->validate(['push_token' => ['required', 'string', 'max:4096']]);
        $session = $request->attributes->get('mobile_session');
        $session->device->update(['push_token' => $data['push_token']]);
        return response()->json(['message' => 'Dispositivo actualizado.']);
    }
}
