<?php

namespace App\Http\Controllers;

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

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $company = $request->user()->company;
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('branches', 'name')->where('company_id', $company->id),
            ],
        ]);

        $company->branches()->create(['name' => $data['name'], 'is_central' => false]);

        return redirect()->route('dashboard')->with('status', 'Sucursal creada.');
    }
}
