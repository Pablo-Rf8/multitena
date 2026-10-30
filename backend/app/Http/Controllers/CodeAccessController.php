<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\CodeAuthAttempt;
use App\Models\CodeLink;
use App\Models\Company;
use App\Models\User;
use App\Services\CodeLinks;
use App\Services\UserAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CodeAccessController extends Controller
{
    public function index(Request $request): View
    {
        $links = CodeLink::with(['user', 'company', 'branch'])->latest('id');
        $this->filters($links, $request);
        $users = User::with(['company', 'branch'])->whereIn('role', ['company_admin', 'branch_admin', 'employee'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->query('company_id')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', '%'.trim($request->query('q')).'%')->orWhere('email', 'like', '%'.trim($request->query('q')).'%')))
            ->orderBy('name')->paginate(20, ['*'], 'users_page')->withQueryString();

        return view('codes.admin', [
            'links' => $links->paginate(20)->withQueryString(),
            'users' => $users,
            'companies' => Company::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function issue(Request $request, User $user, CodeLinks $codes, UserAccess $access): Response
    {
        abort_unless(in_array($user->role, ['company_admin', 'branch_admin', 'employee'], true) && $user->company_id && $user->branch_id && $access->allowed($user), 422);
        [$link, $url] = $codes->issue($user, $request->user());
        return $this->delivery($link, $url);
    }

    public function renew(Request $request, CodeLink $link, CodeLinks $codes, UserAccess $access): Response
    {
        $user = $link->user;
        abort_unless($user && $access->allowed($user), 422);
        [$newLink, $url] = $codes->issue($user, $request->user());
        return $this->delivery($newLink, $url);
    }

    public function suspend(CodeLink $link, CodeLinks $codes): RedirectResponse
    {
        abort_unless($link->status === 'active', 409);
        $codes->changeStatus($link, 'suspended');
        return redirect()->route('codes.admin')->with('status', 'Enlace suspendido y sesiones revocadas.');
    }

    public function revoke(CodeLink $link, CodeLinks $codes): RedirectResponse
    {
        abort_unless($link->status !== 'revoked', 409);
        $codes->changeStatus($link, 'revoked');
        return redirect()->route('codes.admin')->with('status', 'Enlace revocado y sesiones revocadas.');
    }

    public function suspendCompany(Company $company, CodeLinks $codes): RedirectResponse
    {
        DB::transaction(function () use ($company, $codes) {
            foreach (CodeLink::where('company_id', $company->id)->where('status', 'active')->get() as $link) {
                $codes->changeStatus($link, 'suspended');
            }
        });
        return redirect()->route('codes.admin')->with('status', 'Enlaces de '.$company->name.' suspendidos.');
    }

    public function company(Request $request): View
    {
        $viewer = $request->user();
        abort_unless(in_array($viewer->role, ['company_admin', 'branch_admin'], true), 403);
        $links = CodeLink::with(['user', 'branch'])->where('company_id', $viewer->company_id)
            ->when($viewer->role === 'branch_admin', fn ($q) => $q->where('branch_id', $viewer->branch_id));
        $this->filters($links, $request, false);
        return view('codes.company', ['links' => $links->latest('id')->paginate(20)->withQueryString()]);
    }

    public function claim(Request $request, CodeLink $link, CodeLinks $codes): Response
    {
        abort_unless($request->user()->role === 'company_admin' && $link->company_id === $request->user()->company_id, 404);
        $token = DB::transaction(function () use ($link) {
            $locked = CodeLink::whereKey($link->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isUsable() && $locked->handoff_token, 409);
            $token = $locked->handoff_token;
            $locked->update(['handoff_token' => null]);
            return $token;
        });
        return $this->delivery($link, $codes->url($token));
    }

    public function history(Request $request): View
    {
        $attempts = CodeAuthAttempt::with(['user', 'company', 'branch'])->latest('id');
        foreach (['company_id', 'branch_id', 'user_id', 'role', 'result'] as $field) {
            if ($request->filled($field)) $attempts->where($field, $request->query($field));
        }
        if ($request->filled('from')) $attempts->whereDate('created_at', '>=', $request->query('from'));
        if ($request->filled('to')) $attempts->whereDate('created_at', '<=', $request->query('to'));

        $events = ActivityLog::with('actor')->where('event', 'like', 'code_link.%')->latest('id')->limit(50)->get();
        return view('codes.history', [
            'attempts' => $attempts->paginate(30)->withQueryString(),
            'events' => $events,
            'companies' => Company::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'users' => User::whereIn('role', ['company_admin', 'branch_admin', 'employee'])->orderBy('name')->get(['id', 'name', 'company_id']),
        ]);
    }

    private function filters($query, Request $request, bool $global = true): void
    {
        if ($global && $request->filled('company_id')) $query->where('company_id', $request->query('company_id'));
        if ($request->filled('branch_id')) $query->where('branch_id', $request->query('branch_id'));
        if ($request->filled('role')) $query->whereHas('user', fn ($q) => $q->where('role', $request->query('role')));
        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'expired') $query->where('status', 'active')->where('expires_at', '<=', now());
            elseif ($status === 'active') $query->where('status', 'active')->where('expires_at', '>', now());
            elseif (in_array($status, ['suspended', 'revoked'], true)) $query->where('status', $status);
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%'));
        }
    }

    private function delivery(CodeLink $link, string $url): Response
    {
        return response()->view('codes.issued', compact('link', 'url'))->withHeaders([
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
