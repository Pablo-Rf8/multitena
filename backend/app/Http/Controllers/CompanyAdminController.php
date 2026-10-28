<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyAdminController extends Controller
{
    public function create(Company $company): View
    {
        return view('company-admins.form', [
            'company' => $company,
            'managedUser' => null,
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Company $company, Audit $audit): RedirectResponse
    {
        $data = $this->validated($request, $company);
        $user = $company->users()->create($data + ['role' => 'company_admin']);
        $audit->record('user.created', $user, $company->id, ['role' => 'company_admin', 'email' => $user->email]);

        return redirect()->route('companies.show', $company)->with('status', 'Administrador de empresa creado.');
    }

    public function edit(Company $company, int $user): View
    {
        return view('company-admins.form', [
            'company' => $company,
            'managedUser' => $company->users()->where('role', 'company_admin')->findOrFail($user),
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Company $company, int $user, Audit $audit): RedirectResponse
    {
        $user = $company->users()->where('role', 'company_admin')->findOrFail($user);
        $data = $this->validated($request, $company, $user->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        $audit->record('user.updated', $user, $company->id, ['email' => $user->email]);

        return redirect()->route('companies.show', $company)->with('status', 'Administrador actualizado.');
    }

    public function status(Request $request, Company $company, int $user, Audit $audit): RedirectResponse
    {
        $user = $company->users()->where('role', 'company_admin')->findOrFail($user);
        $data = $request->validate(['is_active' => ['required', Rule::in(['0', '1'])]]);
        if ($data['is_active'] === '0' && $user->is_active && $company->users()->where('role', 'company_admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['administrator' => 'La empresa necesita al menos un administrador activo.']);
        }
        $user->update(['is_active' => $data['is_active'] === '1']);
        $audit->record($user->is_active ? 'user.activated' : 'user.suspended', $user, $company->id, ['email' => $user->email]);

        return redirect()->route('companies.show', $company)->with('status', $user->is_active ? 'Administrador activado.' : 'Administrador suspendido.');
    }

    private function validated(Request $request, Company $company, ?int $userId = null): array
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('company_id', $company->id)->where('is_active', true)],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:12', 'confirmed'],
        ]);

        return $data;
    }
}
