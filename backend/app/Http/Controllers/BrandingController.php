<?php

namespace App\Http\Controllers;

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
        ]);
        $company = $request->user()->company;
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company-logos', 'local');
            $data['logo_path'] = $path;
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
}
