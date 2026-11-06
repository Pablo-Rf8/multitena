<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        abort_if($request->session()->has('demo_preview'), 403);
        return view('auth.password');
    }

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        abort_if($request->session()->has('demo_preview'), 403);
        $data = $request->validateWithBag('accountPassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $request->user()->update(['password' => $data['password']]);
        $audit->record('password.changed', $request->user(), $request->user()->company_id);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', __('Contraseña actualizada.'));
    }
}
