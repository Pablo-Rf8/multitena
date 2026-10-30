<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SendAccessPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $noticeId) {}

    public function handle(): void
    {
        $notice = DB::table('access_notifications')->find($this->noticeId);
        $project = config('services.fcm.project_id');
        $file = config('services.fcm.credentials_file');
        if (! $notice || ! $notice->recipient_id || ! $project || ! $file) return;
        $credentials = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $now = time();
        $encode = fn ($value) => rtrim(strtr(base64_encode(json_encode($value, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $header = $encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claims = $encode(['iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging', 'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600]);
        openssl_sign($header.'.'.$claims, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $header.'.'.$claims.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $access = Http::asForm()->post('https://oauth2.googleapis.com/token', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt])->throw()->json('access_token');
        foreach (Device::where('company_id', $notice->company_id)->where('user_id', $notice->recipient_id)->where('status', 'approved')->whereNotNull('push_token')->get() as $device) {
            Http::withToken($access)->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($project).'/messages:send', [
                'message' => ['token' => $device->push_token, 'notification' => ['title' => $notice->title ?: 'Multitena', 'body' => $notice->body ?: 'Tenés una actualización de acceso.']],
            ])->throw();
        }
    }
}
