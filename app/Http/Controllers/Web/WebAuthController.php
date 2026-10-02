<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Accounts;
use App\Support\GoogleIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WebAuthController extends Controller
{
    public function showLogin()
    {
        return view('login', ['googleClientId' => GoogleIdentity::clientId()]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email|max:254',
            'password' => 'required|string|max:200',
        ]);
        $credentials['email'] = strtolower(trim($credentials['email']));

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة']);
        }

        return $this->signedIn($request);
    }

    /** Receives the ID token ("credential") posted by Google Identity Services. */
    public function google(Request $request)
    {
        $request->validate(['credential' => 'required|string|max:4096']);

        $identity = GoogleIdentity::fromIdToken($request->credential);
        if (!$identity) {
            return redirect()->route('login')->withErrors(['email' => 'تعذر التحقق من حساب Google']);
        }

        Auth::login(Accounts::fromGoogle($identity));

        return $this->signedIn($request);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function signedIn(Request $request)
    {
        if (Auth::user()->status === 'rejected') {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'تم رفض طلب انضمام هذا الحساب']);
        }
        // New session id after login prevents session fixation.
        $request->session()->regenerate();
        $request->session()->forget('active_role');

        return redirect()->route('web.index');
    }
}
