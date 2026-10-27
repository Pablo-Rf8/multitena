<?php

namespace App\Http\Controllers;

use App\Services\CreateCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function create(): View
    {
        return view('companies.create');
    }

    public function store(Request $request, CreateCompany $creator): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $company = $creator->handle($data['name'], $data['admin_email'] ?? null, $data['admin_password']);

        return redirect()->route('dashboard')->with('status', 'Se creó '.$company->name.' con su sucursal Central y administrador.');
    }
}
