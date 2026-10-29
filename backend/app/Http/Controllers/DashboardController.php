<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            $companies = Company::query()->withCount(['branches', 'users', 'contacts', 'opportunities']);
            if ($request->filled('q')) {
                $companies->where('name', 'like', '%'.trim((string) $request->query('q')).'%');
            }
            if (in_array($request->query('status'), ['active', 'suspended'], true)) {
                $companies->where('is_active', $request->query('status') === 'active');
            }

            return view('companies.index', [
                'companies' => $companies->with(['branches', 'users'])->latest()->paginate(15)->withQueryString(),
                'companyCount' => Company::count(),
                'activeCount' => Company::where('is_active', true)->count(),
                'contactCount' => Contact::count(),
                'opportunityCount' => Opportunity::count(),
                'userCount' => User::count(),
                'branchCount' => DB::table('branches')->count(),
                'pendingCount' => User::where('role', 'employee')->where('approval_status', 'pending')->count(),
            ]);
        }

        abort_unless(in_array($user->role, ['company_admin', 'branch_admin', 'employee'], true) && $user->company_id && $user->branch_id, 403);

        if ($user->role === 'employee') {
            return view('employees.dashboard', [
                'company' => $user->company,
                'branch' => $user->branch,
                'permissions' => $user->category?->permissions ?? [],
                'contactCount' => Contact::where('company_id', $user->company_id)->where('branch_id', $user->branch_id)->count(),
                'opportunityCount' => Opportunity::where('company_id', $user->company_id)->where('branch_id', $user->branch_id)->count(),
            ]);
        }

        return view('companies.dashboard', [
            'company' => $user->company()->with($user->role === 'company_admin' ? ['branches', 'users'] : [])->firstOrFail(),
            'branch' => $user->branch,
            'isCompanyAdmin' => $user->role === 'company_admin',
            'contactCount' => Contact::where('company_id', $user->company_id)
                ->when($user->role === 'branch_admin', fn ($q) => $q->where('branch_id', $user->branch_id))->count(),
            'opportunityCount' => Opportunity::where('company_id', $user->company_id)
                ->when($user->role === 'branch_admin', fn ($q) => $q->where('branch_id', $user->branch_id))->count(),
            'wonAmount' => Opportunity::where('company_id', $user->company_id)
                ->when($user->role === 'branch_admin', fn ($q) => $q->where('branch_id', $user->branch_id))
                ->where('stage', 'won')->sum('amount_cents'),
        ]);
    }
}
