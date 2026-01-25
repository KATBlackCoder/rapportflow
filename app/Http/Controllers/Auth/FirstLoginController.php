<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class FirstLoginController extends Controller
{
    /**
     * Show the first login page.
     */
    public function show(): Response
    {
        return Inertia::render('auth/FirstLogin');
    }

    /**
     * Handle the first login password choice.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasChangedPassword()) {
            return redirect()->route('dashboard');
        }

        $rules = [
            'action' => ['required', 'in:keep,change'],
        ];

        if ($request->input('action') === 'change') {
            $rules['password'] = ['required', 'string', Password::default(), 'confirmed'];
        }

        $validated = $request->validate($rules);

        if ($validated['action'] === 'keep') {
            // User wants to keep the default password
            $user->password_changed_at = now();
            $user->save();
        } else {
            // User wants to change the password
            $user->password = Hash::make($validated['password']);
            $user->password_changed_at = now();
            $user->save();
        }

        return redirect()->route('dashboard');
    }
}
