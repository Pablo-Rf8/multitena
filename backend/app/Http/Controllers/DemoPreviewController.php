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
    public function start(Request $request, Company $company, Audit $audit, WebSessionStarter $sessions): RedirectResponse
    {
        abort_unless($company->is_demo && $company->is_active, 403);

        $demoAdmin = $company->users()->where('role', 'company_admin')->where('is_active', true)
            ->whereHas('branch', fn ($query) => $query->where('is_active', true))->orderBy('id')->first();
        if (! $demoAdmin) {
            return back()->withErrors(['demo_preview' => __('Esta empresa DEMO necesita un administrador activo para probarla.')]);
        }

        $superAdmin = $request->user();
        $audit->record('demo_preview.started', $company, $company->id, ['demo_user_id' => $demoAdmin->id]);
        $this->closeCurrentSession($request, 'demo_preview_started');
        Auth::login($demoAdmin);
        $sessions->start($request, $demoAdmin, 'demo_preview');
        $request->session()->put('demo_preview', [
            'super_admin_id' => $superAdmin->id,
            'company_id' => $company->id,
            'demo_user_id' => $demoAdmin->id,
        ]);

        return redirect()->route('credentials.index')->with('status', __('Estás probando :company como administrador DEMO.', ['company' => $company->name]));
    }

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
