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

        abort_unless($user->role === 'company_admin' && $user->company_id && $user->branch_id, 403);

        return view('companies.dashboard', [
            'company' => $user->company()->with('branches')->firstOrFail(),
            'branch' => $user->branch,
        ]);
    }
}
