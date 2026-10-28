<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchUserController extends Controller
{
    public function create(Request $request): View
    {
        return view('branch-users.create', [
            'branches' => $request->user()->company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Audit $audit): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);
        $company = $request->user()->company;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'branch_id' => [
                'required', 'integer',
                Rule::exists('branches', 'id')->where('company_id', $company->id)->where('is_active', true),
            ],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = $company->users()->create([
            'name' => trim($data['name']),
            'email' => strtolower($data['email']),
            'branch_id' => $data['branch_id'],
            'password' => $data['password'],
            'role' => 'branch_admin',
        ]);
        $audit->record('user.created', $user, $company->id, ['role' => 'branch_admin', 'email' => $user->email]);

        return redirect()->route('dashboard')->with('status', 'Administrador de sucursal creado.');
    }

    public function edit(Request $request, int $user): View
    {
        $company = $request->user()->company;
        $user = $company->users()->where('role', 'branch_admin')->findOrFail($user);

        return view('branch-users.edit', [
            'managedUser' => $user,
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $user, Audit $audit): RedirectResponse
    {
        $company = $request->user()->company;
        $user = $company->users()->where('role', 'branch_admin')->findOrFail($user);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('company_id', $company->id)->where('is_active', true)],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        $audit->record('user.updated', $user, $company->id, ['email' => $user->email]);

        return redirect()->route('dashboard')->with('status', 'Administrador actualizado.');
    }

    public function status(Request $request, int $user, Audit $audit): RedirectResponse
    {
        $company = $request->user()->company;
        $user = $company->users()->where('role', 'branch_admin')->findOrFail($user);
        $data = $request->validate(['is_active' => ['required', Rule::in(['0', '1'])]]);
        $user->update(['is_active' => $data['is_active'] === '1']);
        $audit->record($user->is_active ? 'user.activated' : 'user.suspended', $user, $company->id, ['email' => $user->email]);

        return redirect()->route('dashboard')->with('status', $user->is_active ? 'Usuario activado.' : 'Usuario suspendido.');
    }
}
