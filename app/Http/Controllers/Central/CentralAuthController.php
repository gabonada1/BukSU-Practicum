<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralLoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CentralAuthController extends Controller
{
    public function create(): View
    {
        return view('central.auth.login', [
            'pageTitle' => 'University Administration Login | '.config('app.name', 'University Practicum'),
            'loginAction' => route('central.login.store'),
        ]);
    }

    public function store(CentralLoginRequest $request): RedirectResponse
    {
        if (! Auth::guard('central_superadmin')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The provided University Administration credentials are invalid.',
            ]);
        }

        $request->session()->regenerate();

        return $this->forgetLegacyCentralCookies(
            redirect()->route('central.dashboard')
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('central_superadmin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->forgetLegacyCentralCookies(
            redirect()->route('central.login')
        );
    }

    protected function forgetLegacyCentralCookies(RedirectResponse $response): RedirectResponse
    {
        $baseCookie = Str::slug((string) config('app.name', 'laravel')).'-session';
        $cookies = [
            [$baseCookie.'-central', '/central'],
            [$baseCookie.'-central', '/'],
            [$baseCookie.'-central-v2', '/central'],
        ];

        foreach ($cookies as [$name, $path]) {
            $response->withCookie(Cookie::forget($name, $path));
        }

        return $response;
    }
}
