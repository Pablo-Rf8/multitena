<?php

namespace App\Services;

use App\Models\CodeCredential;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TemporaryCodes
{
    public function credentialFor(User $user): CodeCredential
    {
        return CodeCredential::firstOrCreate(
            ['user_id' => $user->id],
            ['secret' => base64_encode(random_bytes(20))]
        );
    }

    public function step(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? now()->timestamp, 30);
    }

    public function code(CodeCredential $credential, ?int $step = null): string
    {
        $secret = base64_decode($credential->secret, true);
        if ($secret === false) throw new \RuntimeException('Secreto TOTP inválido.');

        $step ??= $this->step();
        $digest = hash_hmac('sha1', pack('N2', intdiv($step, 4294967296), $step % 4294967296), $secret, true);
        $offset = ord($digest[19]) & 0x0f;
        $number = ((ord($digest[$offset]) & 0x7f) << 24)
            | ((ord($digest[$offset + 1]) & 0xff) << 16)
            | ((ord($digest[$offset + 2]) & 0xff) << 8)
            | (ord($digest[$offset + 3]) & 0xff);

        return sprintf('%06d', $number % 1000000);
    }

    public function verifyAndConsume(CodeCredential $credential, string $submitted): string
    {
        $step = $this->step();
        if (! preg_match('/^\d{6}$/D', $submitted) || ! hash_equals($this->code($credential, $step), $submitted)) {
            return 'incorrect';
        }

        $updated = DB::table('code_credentials')->where('id', $credential->id)
            ->where(fn ($query) => $query->whereNull('last_used_step')->orWhere('last_used_step', '<', $step))
            ->update(['last_used_step' => $step, 'updated_at' => now()]);

        return $updated === 1 ? 'valid' : 'reused';
    }
}
