<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()->load(['company', 'branch', 'category'])]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()->load(['company', 'branch', 'category'])]);
    }

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $user = $request->user();
        $user->update($data);
        $audit->record('profile.updated', $user, $user->company_id);

        return redirect()->route('profile.show')->with('status', __('Perfil actualizado.'));
    }
}
