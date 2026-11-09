<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShareLimits
{
    public function validated(Request $request, array $extra = []): array
    {
        $data = $request->validate(array_merge([
            'user_id' => ['required', 'integer'],
            'max_accesses' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'duration_value' => ['nullable', 'required_with:duration_unit', 'integer', 'min:1', 'max:365'],
            'duration_unit' => ['nullable', 'required_with:duration_value', Rule::in(['hours', 'days', 'weeks', 'months'])],
        ], $extra));
        if (empty($data['max_accesses']) && empty($data['duration_value'])) {
            throw ValidationException::withMessages(['max_accesses' => 'Indicá un máximo de accesos, una duración o ambos.']);
        }

        return $data;
    }

    public function expiresAt(array $data): ?\Illuminate\Support\Carbon
    {
        if (empty($data['duration_value'])) {
            return null;
        }

        $duration = (int) $data['duration_value'];

        return match ($data['duration_unit']) {
            'hours' => now()->addHours($duration),
            'days' => now()->addDays($duration),
            'weeks' => now()->addWeeks($duration),
            'months' => now()->addMonthsNoOverflow($duration),
        };
    }

    public function consume(string $table, int $id, int $userId): bool
    {
        abort_unless(in_array($table, ['credential_shares', 'credential_template_shares'], true), 500);

        return DB::table($table)->where('id', $id)->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('max_accesses')->orWhereColumn('used_accesses', '<', 'max_accesses'))
            ->increment('used_accesses') === 1;
    }
}
