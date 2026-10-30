<?php

namespace App\Services;

use App\Models\CodeLink;
use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CodeLinks
{
    public function issue(User $user, User $actor): array
    {
        return DB::transaction(function () use ($user, $actor) {
            app(TemporaryCodes::class)->credentialFor($user);
            $previousLinks = CodeLink::where('user_id', $user->id)->where('status', 'active')->get();
            foreach ($previousLinks as $old) {
                $old->update(['status' => 'revoked', 'revoked_at' => now(), 'handoff_token' => null]);
                app(Audit::class)->record('code_link.revoked_on_renewal', $old, $old->company_id);
            }
            if ($previousLinks->isNotEmpty()) $this->revokeSessions($user, 'code_link_renewed');

            $token = bin2hex(random_bytes(32));
            $issuedAt = now();
            $link = CodeLink::create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
                'issued_by' => $actor->id,
                'token_hash' => hash('sha256', $token),
                'handoff_token' => $token,
                'issued_at' => $issuedAt,
                'expires_at' => $issuedAt->copy()->addHours(120),
            ]);
            app(Audit::class)->record('code_link.issued', $link, $link->company_id, ['user_id' => $user->id]);

            return [$link, $this->url($token)];
        });
    }

    public function changeStatus(CodeLink $link, string $status): void
    {
        DB::transaction(function () use ($link, $status) {
            $locked = CodeLink::whereKey($link->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === $status) return;
            $locked->update([
                'status' => $status,
                'handoff_token' => null,
                'suspended_at' => $status === 'suspended' ? now() : $locked->suspended_at,
                'revoked_at' => $status === 'revoked' ? now() : $locked->revoked_at,
            ]);
            $this->revokeSessions($locked->user, 'code_link_'.$status);
            app(Audit::class)->record('code_link.'.$status, $locked, $locked->company_id, ['user_id' => $locked->user_id]);
        });
    }

    public function revokeSessions(User $user, string $reason): void
    {
        $sessions = MobileSession::where('user_id', $user->id)->where('status', 'active')->get();
        $tokenIds = $sessions->pluck('token_id')->filter()->all();
        if ($tokenIds) DB::table('personal_access_tokens')->whereIn('id', $tokenIds)->delete();
        MobileSession::whereIn('id', $sessions->pluck('id'))->update([
            'status' => 'revoked', 'ended_at' => now(), 'end_reason' => $reason, 'updated_at' => now(),
        ]);
    }

    public function url(string $token): string
    {
        return rtrim((string) config('codes.generator_url'), '#').'#'.$token;
    }
}
