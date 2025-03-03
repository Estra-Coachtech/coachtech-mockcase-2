<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠管理システム</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/admin-login.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="/">
                <img class="header__logo--img" src="{{ asset('images/logo.svg') }}" alt="logo">
            </a>
        </div>
    </header>
    <main>
        <div class="login__content">
            <div class="login__heading">
                <h2 class="login__heading--item">管理者ログイン</h2>
            </div>
            <form class="form" action="/admin/login" method="post">
                @csrf
                <div class="form__group">
                    <span class="form__label">メールアドレス</span>
                    <input class="form__input" type="email" name="email" value="{{ old('email') }}">
                    <div class="form__error">
                        @error ('email')
                        {{ $message }}
                        @enderror
                    </div>
                </div>
                <div class="form__group">
                    <span class="form__label">パスワード</span>
                    <input class="form__input" type="password" name="password">
                    <div class="form__error">
                        @error ('password')
                        {{ $message }}
                        @enderror
                    </div>
                </div>
                <div class="form__button">
                    <button class="form__button--submit">管理者ログインする</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>