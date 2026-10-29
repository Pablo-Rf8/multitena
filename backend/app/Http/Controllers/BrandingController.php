<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingController extends Controller
{
    public function edit(Request $request): View
    {
        return view('branding.edit', ['company' => $request->user()->company]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'theme_primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'logo_mode' => ['nullable', 'in:custom,demo'],
        ]);
        $company = $request->user()->company;
        if (($data['logo_mode'] ?? null) === 'demo' && ! in_array($company->slug, ['pepsi-demo', 'coca-cola-demo'], true)) {
            return back()->withErrors(['logo_mode' => 'Esta empresa no tiene logotipo DEMO.']);
        }
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company-logos', 'local');
            $data['logo_path'] = $path;
            $data['logo_mode'] = 'custom';
        }
        unset($data['logo']);
        $company->update($data);

        return back()->with('status', 'Identidad visual actualizada.');
    }

    public function logo(Request $request): BinaryFileResponse
    {
        $path = $request->user()->company?->logo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, max-age=300']);
    }

    public function publicLogo(string $code): BinaryFileResponse
    {
        $company = Company::where('code', $code)->where('is_active', true)->firstOrFail();
        abort_unless($company->logo_path && Storage::disk('local')->exists($company->logo_path), 404);

        return response()->file(Storage::disk('local')->path($company->logo_path), ['Cache-Control' => 'public, max-age=300']);
    }
}
