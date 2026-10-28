<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function create(): View
    {
        return view('branches.create');
    }

    public function store(Request $request, Audit $audit): RedirectResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $company = $request->user()->company;
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('branches', 'name')->where('company_id', $company->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $branch = $company->branches()->create($data + ['is_central' => false]);
        $audit->record('branch.created', $branch, $company->id, ['name' => $branch->name]);

        return redirect()->route('dashboard')->with('status', 'Sucursal creada.');
    }

    public function edit(Request $request, int $branch): View
    {
        $branch = $request->user()->company->branches()->findOrFail($branch);

        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, int $branch, Audit $audit): RedirectResponse
    {
        $company = $request->user()->company;
        $branch = $company->branches()->findOrFail($branch);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('branches', 'name')->where('company_id', $company->id)->ignore($branch->id), ...($branch->is_central ? [Rule::in(['Central'])] : [])],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $branch->update($data);
        $audit->record('branch.updated', $branch, $company->id, ['name' => $branch->name]);

        return redirect()->route('dashboard')->with('status', 'Sucursal actualizada.');
    }

    public function status(Request $request, int $branch, Audit $audit): RedirectResponse
    {
        $branch = $request->user()->company->branches()->findOrFail($branch);
        abort_if($branch->is_central, 422, 'La sucursal Central debe permanecer activa.');
        $data = $request->validate(['is_active' => ['required', Rule::in(['0', '1'])]]);
        if ($data['is_active'] === '0' && $branch->users()->where('role', 'company_admin')->where('is_active', true)->exists()) {
            return back()->withErrors(['branch' => 'Reasigná los administradores de empresa antes de suspender esta sucursal.']);
        }
        $branch->update(['is_active' => $data['is_active'] === '1']);
        $audit->record($branch->is_active ? 'branch.activated' : 'branch.suspended', $branch, $branch->company_id);

        return redirect()->route('dashboard')->with('status', $branch->is_active ? 'Sucursal activada.' : 'Sucursal suspendida. Sus usuarios no podrán ingresar.');
    }
}
