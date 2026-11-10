<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate(); // WAJIB: cegah Session Fixation

            session(['login_timestamp' => now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB']);
            Cookie::queue('del_last_user_email', $request->email, 10080); // 7 hari

            return redirect()->intended(route('buku.index'))
                ->with('success', 'Sesi Anda telah aktif.');
        }

        return back()->withInput($request->only('email'))
            ->with('error', 'Kredensial login tidak valid.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Sesi server telah dihapus dengan aman.');
    }
}