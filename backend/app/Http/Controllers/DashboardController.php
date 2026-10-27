<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            return view('companies.index', [
                'companies' => Company::with(['branches', 'users'])->latest()->get(),
            ]);
        }

        abort_unless(in_array($user->role, ['company_admin', 'branch_admin'], true) && $user->company_id && $user->branch_id, 403);

        return view('companies.dashboard', [
            'company' => $user->company()->with($user->role === 'company_admin' ? ['branches', 'users'] : [])->firstOrFail(),
            'branch' => $user->branch,
            'isCompanyAdmin' => $user->role === 'company_admin',
        ]);
    }
}
