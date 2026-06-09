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

    /**
     * 依存するユーザー作成アクションを注入する。
     *
     * @param  CreateNewUser  $creator  ユーザー作成処理を担う Fortify アクション
     */
    public function __construct(CreateNewUser $creator)
    {
        $this->creator = $creator;
    }

    /**
     * 管理者ログイン画面を表示する。
     *
     * @return View  管理者ログイン画面のビュー
     */
    public function adminLogin(): View
    {
        return view('admin/admin-login');
    }

    /**
     * 管理者ログインを実行する。管理者でなければログインさせない。
     *
     * @param  AdminLoginRequest  $request  メールアドレス・パスワードを含むログインリクエスト
     * @return RedirectResponse  勤怠一覧へのリダイレクト、または認証失敗時の差し戻し
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
     *
     * @return RedirectResponse  管理者ログイン画面へのリダイレクト
     */
    public function adminLogout(): RedirectResponse
    {
        Auth::logout();
        return redirect('/admin/login');
    }

    /**
     * 会員登録を行い、認証メールを送信したうえでメール認証誘導画面へ遷移する。
     *
     * @param  RegisterRequest  $request  名前・メールアドレス・パスワードを含む登録リクエスト
     * @return RedirectResponse  メール認証誘導画面へのリダイレクト
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
     *
     * @param  LoginRequest  $request  メールアドレス・パスワードを含むログインリクエスト
     * @return RedirectResponse  勤怠打刻画面へのリダイレクト、または認証失敗時の差し戻し
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
     *
     * @return RedirectResponse  ログイン画面へのリダイレクト
     */
    public function doLogout(): RedirectResponse
    {
        Auth::logout();
        return redirect('/login');
    }

    /**
     * 指定ユーザーへメール認証通知を送信する。
     *
     * @param  User  $user  認証メールの送信先ユーザー
     * @return void
     */
    protected function sendVerificationEmail(User $user): void
    {
        $user->sendEmailVerificationNotification();
    }
}
