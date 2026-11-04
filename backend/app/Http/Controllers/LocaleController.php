<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', Rule::in(array_keys(config('locales.available')))]]);
        $request->session()->put('locale', $data['locale']);
        if ($request->user() && $request->boolean('return_to_account')) {
            $request->session()->flash('reopen_account', true);
        }

        return back();
    }
}
