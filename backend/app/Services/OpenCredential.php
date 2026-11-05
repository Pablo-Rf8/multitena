<?php

namespace App\Services;

use App\Models\Credential;
use App\Models\CredentialAccess;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OpenCredential
{
    public function __construct(private ShareLimits $limits) {}

    public function handle(Credential $credential, User $user, Audit $audit): void
    {
        $shareId = DB::transaction(function () use ($credential, $user, $audit) {
            $shareId = null;
            if ($credential->owner_id !== $user->id && ! $credential->grants()->where('user_id', $user->id)->exists()) {
                $shares = $credential->shares()->where('user_id', $user->id)->available()->orderBy('id')->pluck('id');
                foreach ($shares as $id) {
                    if ($this->limits->consume('credential_shares', $id, $user->id)) {
                        $shareId = $id;
                        break;
                    }
                }
                abort_if($shareId === null, 403);
            }

            CredentialAccess::create([
                'company_id' => $credential->company_id,
                'credential_id' => $credential->id,
                'share_id' => $shareId,
                'user_id' => $user->id,
                'opened_at' => now(),
            ]);
            $audit->record('credential.viewed', $credential, $credential->company_id);

            return $shareId;
        }, 3);

        if ($shareId !== null) {
            session()->put('credential_share_open.'.$credential->id, [
                'user_id' => $user->id, 'share_id' => $shareId, 'opened_at' => now()->timestamp,
            ]);
        } else {
            session()->forget('credential_share_open.'.$credential->id);
        }
    }
}
