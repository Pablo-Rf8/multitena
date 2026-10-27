<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchUserController extends Controller
{
    public function create(Request $request): View
    {
        return view('branch-users.create', [
            'branches' => $request->user()->company->branches()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
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
                Rule::exists('branches', 'id')->where('company_id', $company->id),
            ],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $company->users()->create([
            'name' => trim($data['name']),
            'email' => strtolower($data['email']),
            'branch_id' => $data['branch_id'],
            'password' => $data['password'],
            'role' => 'branch_admin',
        ]);

        return redirect()->route('dashboard')->with('status', 'Administrador de sucursal creado.');
    }
}
