<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\MobileSession;
use App\Models\User;
use App\Services\Audit;
use App\Services\WebSessionStarter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoPreviewController extends Controller
{
    public function stop(Request $request, Audit $audit, WebSessionStarter $sessions): RedirectResponse
    {
        $preview = $request->session()->get('demo_preview');
        abort_unless(is_array($preview), 403);
        $superAdmin = User::whereKey($preview['super_admin_id'] ?? 0)
            ->where('role', 'super_admin')->where('is_active', true)->firstOrFail();
        $company = Company::whereKey($preview['company_id'] ?? 0)->where('is_demo', true)->firstOrFail();

        $audit->record('demo_preview.ended', $company, $company->id, ['demo_user_id' => $request->user()->id]);
        $this->closeCurrentSession($request, 'demo_preview_ended');
        $request->session()->forget('demo_preview');
        Auth::login($superAdmin);
        $sessions->start($request, $superAdmin, 'demo_return');

        return redirect()->route('companies.show', $company)->with('status', __('Volviste a la administración global.'));
    }

    private function closeCurrentSession(Request $request, string $reason): void
    {
        MobileSession::whereKey($request->session()->get('access_session_id'))
            ->where('user_id', $request->user()->id)->where('status', 'active')
            ->update(['status' => 'closed', 'ended_at' => now(), 'end_reason' => $reason]);
    }
}
