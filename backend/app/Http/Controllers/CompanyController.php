<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Services\Audit;
use App\Services\CompanyDomains;
use App\Services\CreateCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function create(): View
    {
        return view('companies.create');
    }

    public function store(Request $request, CreateCompany $creator, Audit $audit, CompanyDomains $domains): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
            'currency' => ['nullable', Rule::in(['GTQ', 'USD', 'MXN', 'EUR', 'COP', 'CRC'])],
            'email_domains' => ['nullable', 'string', 'max:1000'],
        ]);
        $configuredDomains = $domains->parse($data['email_domains'] ?? null);
        $domains->assertAvailable($configuredDomains);
        $company = $creator->handle($data['name'], $data['admin_email'] ?? null, $data['admin_password'], false, $data['currency'] ?? 'GTQ');
        CompanySetting::updateOrCreate(['company_id' => $company->id], ['email_domains' => $configuredDomains]);

        $audit->record('company.created', $company, $company->id, ['name' => $company->name]);

        return redirect()->route('dashboard')->with('status', 'Se creó '.$company->name.' con su sucursal Central y administrador.');
    }

    public function show(Company $company): View
    {
        return view('companies.show', [
            'company' => $company->load(['branches' => fn ($q) => $q->orderByDesc('is_central')->orderBy('name'), 'users' => fn ($q) => $q->where('role', '!=', 'employee')->orderBy('name')]),
            'userCount' => $company->users()->count(),
            'employeeCount' => $company->users()->where('role', 'employee')->count(),
            'contactCount' => $company->contacts()->count(),
            'opportunityCount' => $company->opportunities()->count(),
            'activity' => ActivityLog::with('actor')->where('company_id', $company->id)->latest()->limit(15)->get(),
            'domains' => CompanySetting::firstOrCreate(['company_id' => $company->id])->email_domains ?? [],
        ]);
    }

    public function employees(Company $company): View
    {
        return view('companies.employees', [
            'company' => $company,
            'employees' => $company->users()->where('role', 'employee')->with(['branch', 'category'])->orderBy('name')->paginate(20),
        ]);
    }

    public function edit(Company $company): View
    {
        $domains = CompanySetting::firstOrCreate(['company_id' => $company->id])->email_domains ?? [];
        return view('companies.edit', compact('company', 'domains'));
    }

    public function update(Request $request, Company $company, Audit $audit, CompanyDomains $domains): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('companies', 'name')->ignore($company->id)],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'currency' => ['required', Rule::in(['GTQ', 'USD', 'MXN', 'EUR', 'COP', 'CRC'])],
            'email_domains' => ['nullable', 'string', 'max:1000'],
        ]);
        $configuredDomains = $domains->parse($data['email_domains'] ?? null);
        $domains->assertAvailable($configuredDomains, $company->id);
        if ($data['currency'] !== $company->currency && $company->opportunities()->exists()) {
            throw ValidationException::withMessages(['currency' => 'No podés cambiar la moneda mientras existan oportunidades.']);
        }
        $company->update(collect($data)->except('email_domains')->all());
        CompanySetting::updateOrCreate(['company_id' => $company->id], ['email_domains' => $configuredDomains]);
        $audit->record('company.updated', $company, $company->id, ['name' => $company->name]);

        return redirect()->route('companies.show', $company)->with('status', 'Empresa actualizada.');
    }

    public function status(Request $request, Company $company, Audit $audit): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', Rule::in(['0', '1'])]]);
        $company->update(['is_active' => $data['is_active'] === '1']);
        $audit->record($company->is_active ? 'company.activated' : 'company.suspended', $company, $company->id);

        return redirect()->route('companies.show', $company)->with('status', $company->is_active ? 'Empresa activada.' : 'Empresa suspendida. Sus usuarios no podrán ingresar.');
    }
}
