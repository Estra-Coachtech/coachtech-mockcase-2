<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected CreateNewUser $creator;

    public function __construct(CreateNewUser $creator)
    {
        $this->creator = $creator;
    }

    /**
     * 管理者ログイン画面を表示する。
     */
    public function adminLogin(): View
    {
        return view('admin/admin-login');
    }

    /**
     * 管理者ログインを実行する。管理者でなければログインさせない。
     */
    public function adminDoLogin(AdminLoginRequest $request): RedirectResponse
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

    /**
     * 管理者をログアウトする。
     */
    public function adminLogout(): RedirectResponse
    {
        Auth::logout();
        return redirect('/admin/login');
    }

    /**
     * 会員登録を行い、認証メールを送信したうえでメール認証誘導画面へ遷移する。
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->creator->create($request->all());
        $user->sendEmailVerificationNotification();

        // 登録後はそのままログインさせ、メール認証誘導画面へ遷移する
        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    /**
     * 一般ユーザーのログインを実行する。メール未認証の場合はログインさせず認証メールを再送する。
     */
    public function doLogin(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (! $user->hasVerifiedEmail()) {
                Auth::logout();
                $this->sendVerificationEmail($user);
                return redirect()->back()->withErrors([
                    'email' => 'メール認証が必要です。認証メールを再送信しました。'
                ]);
            }
            return redirect()->intended('/attendance');
        }

        return redirect()->back()->withErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }

    /**
     * 一般ユーザーをログアウトする。
     */
    public function doLogout(): RedirectResponse
    {
        Auth::logout();
        return redirect('/login');
    }

    /**
     * 指定ユーザーへメール認証通知を送信する。
     */
    protected function sendVerificationEmail(User $user): void
    {
        $user->sendEmailVerificationNotification();
    }
}
