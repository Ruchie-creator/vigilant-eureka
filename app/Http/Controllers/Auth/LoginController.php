<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $password = $credentials['password'];

        if (! $user) {
            return back()->withErrors(['email' => 'These credentials do not match the admin account.'])->onlyInput('email');
        }

        try {
            $passwordMatches = Hash::check($password, $user->password);
        } catch (\RuntimeException) {
            $passwordMatches = false;
        }
        $legacyMd5Matches = strlen($user->password) === 32 && hash_equals($user->password, md5($password));

        if (! $passwordMatches && ! $legacyMd5Matches) {
            return back()->withErrors(['email' => 'These credentials do not match the admin account.'])->onlyInput('email');
        }

        if ($legacyMd5Matches) {
            $user->forceFill(['password' => Hash::make($password)])->save();
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
