<?php
namespace App\Http\Controllers;

use App\Models\{CompanySetting, Device, MobileSession, User};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->company || ! hash_equals((string) $user->company->code, $data['company_code']) || ! $user->is_active || ! $user->company->is_active || ! $user->branch?->is_active || ($user->role === 'employee' && $user->approval_status !== 'approved')) {
            return response()->json(['message' => 'Credenciales o acceso inválidos.'], 401);
        }
        $settings = CompanySetting::firstOrCreate(['company_id' => $user->company_id]);
        if ($user->role === 'employee' && ! $settings->employee_access_enabled) {
            return response()->json(['message' => 'Acceso de empleados deshabilitado.'], 403);
        }
        $device = Device::firstOrCreate(
            ['user_id' => $user->id, 'identifier_hash' => hash('sha256', $data['device_id'])],
            ['company_id' => $user->company_id, 'name' => $data['device_name'], 'status' => $settings->require_device_approval ? 'pending' : 'approved']
        );
        if ($device->status !== 'approved') {
            if ($device->wasRecentlyCreated) {
                DB::table('access_notifications')->insert(['company_id' => $user->company_id, 'kind' => 'device_pending', 'subject_id' => $device->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            return response()->json(['message' => 'Dispositivo pendiente de aprobación.'], 403);
        }
        $expires = now()->addHours($settings->token_lifetime_hours);
        $token = $user->createToken('mobile:'.$user->company_id.':'.$device->id, ['mobile'], $expires);
        MobileSession::create([
            'company_id' => $user->company_id, 'user_id' => $user->id, 'device_id' => $device->id,
            'token_id' => $token->accessToken->id, 'started_at' => now(), 'expires_at' => $expires,
        ]);
        $device->update(['last_seen_at' => now()]);
        return response()->json(['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $expires->toIso8601String(), 'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'company_id' => $user->company_id]]);
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
}
