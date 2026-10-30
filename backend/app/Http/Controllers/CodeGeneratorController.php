<?php

namespace App\Http\Controllers;

use App\Models\CodeLink;
use App\Services\TemporaryCodes;
use App\Services\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;

class CodeGeneratorController extends Controller
{
    public function access(): Response
    {
        return $this->privateResponse(response()->view('codes.access'));
    }

    public function verify(Request $request, UserAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'link_token' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $link = CodeLink::with(['user.company', 'user.branch'])->where('token_hash', hash('sha256', $data['link_token']))->first();
        $user = $link?->user;
        $validIdentity = $user
            && hash_equals(strtolower($user->email), strtolower(trim($data['email'])))
            && Hash::check($data['password'], $user->password)
            && $user->role !== 'super_admin'
            && $user->company_id === $link->company_id
            && $user->branch_id === $link->branch_id
            && $access->allowed($user);

        if (! $validIdentity) {
            return redirect()->away('/codes/access')->withErrors(['identity' => 'Enlace o identidad inválidos. Abrí de nuevo tu enlace e intentá otra vez.']);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'code_grant_link_id' => $link->id,
            'code_grant_user_id' => $user->id,
            'code_grant_verified_at' => now()->timestamp,
        ]);

        return redirect()->away('/codes/generator');
    }

    public function generator(Request $request, UserAccess $access, TemporaryCodes $codes): Response|RedirectResponse
    {
        $link = $this->grant($request);
        if (! $link) return redirect()->away('/codes/access')->withErrors(['identity' => 'Verificación vencida. Abrí tu enlace y verificá tu identidad otra vez.']);

        $accountAllowed = $access->allowed($link->user);
        $usable = $link->isUsable() && $accountAllowed;
        $credential = $usable ? $codes->credentialFor($link->user) : null;
        $step = $codes->step();

        return $this->privateResponse(response()->view('codes.generator', [
            'link' => $link,
            'status' => $usable ? 'active' : ($link->status !== 'active' ? $link->status : ($link->isUsable() ? 'account_disabled' : 'expired')),
            'code' => $usable && ($credential->last_used_step === null || $credential->last_used_step < $step) ? $codes->code($credential, $step) : null,
            'seconds' => 30 - (now()->timestamp % 30),
        ]));
    }

    public function current(Request $request, UserAccess $access, TemporaryCodes $codes): JsonResponse
    {
        $link = $this->grant($request);
        if (! $link) return $this->privateResponse(response()->json(['status' => 'verification_expired'], 401));
        if (! $link->isUsable() || ! $access->allowed($link->user)) {
            $status = $link->status !== 'active' ? $link->status : ($link->isUsable() ? 'account_disabled' : 'expired');
            return $this->privateResponse(response()->json(['status' => $status], 403));
        }

        $credential = $codes->credentialFor($link->user);
        $step = $codes->step();
        return $this->privateResponse(response()->json([
            'status' => 'active',
            'code' => $credential->last_used_step === null || $credential->last_used_step < $step ? $codes->code($credential, $step) : null,
            'seconds' => 30 - (now()->timestamp % 30),
        ]));
    }

    private function grant(Request $request): ?CodeLink
    {
        if (now()->timestamp - (int) $request->session()->get('code_grant_verified_at', 0) > 1800) return null;
        $link = CodeLink::with(['user.company', 'user.branch'])->find($request->session()->get('code_grant_link_id'));
        if (! $link || ! $link->user || $link->user_id !== $request->session()->get('code_grant_user_id')) return null;
        if ($link->user->company_id !== $link->company_id || $link->user->branch_id !== $link->branch_id) return null;
        return $link;
    }

    private function privateResponse(mixed $response): mixed
    {
        return $response->withHeaders([
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
