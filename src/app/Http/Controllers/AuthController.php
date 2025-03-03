<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use App\Actions\Fortify\CreateNewUser;


class AuthController extends Controller
{
    protected $creator;

    public function __construct(CreateNewUser $creator)
    {
        $this->creator = $creator;
    }

    public function adminLogin()
    {
        return view('admin/admin-login');
    }

    public function adminDoLogin(AdminLoginRequest $request)
    {
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            if ($user->admin_status) {
                return redirect('admin/attendance/list');
            } else {
                Auth::logout();
                return redirect()->back()->withErrors([
                    'email' => 'ログイン情報が登録されていません'
                ]);
            }
        }
        return redirect()->back()->withErrors([
        'email' => 'ログイン情報が登録されていません'
        ])->withInput();
    }

    public function adminLogout()
    {
        Auth::logout();
        return redirect('/admin/login');
    }


    public function store(RegisterRequest $request)
    {
        $user = $this->creator->create($request->all());
        $user->sendEmailVerificationNotification();
        return redirect('/register')->with('message', '登録が完了しました。認証メールを送信しましたのでご確認ください。');
    }

    public function doLogin(LoginRequest $request)
    {

    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            Auth::logout();
            $this->sendVerificationEmail($user);
            return redirect()->back()->withErrors([
            'email' => 'メール認証が必要です。認証メールを再送信しました。'
            ]);
        }
        return redirect()->intended('/login');
    }

    return redirect()->back()->withErrors([
        'email' => 'ログイン情報が登録されていません'
    ]);
    }

    protected function sendVerificationEmail($user)
    {
        $user->sendEmailVerificationNotification();
    }


}