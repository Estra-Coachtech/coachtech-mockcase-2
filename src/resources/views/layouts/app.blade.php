<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠管理システム</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
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
            @if(Auth::check())
            <form action="/logout" method="post">
                @csrf
                <div class="inner__group">
                    <a class="inner__group--item" href="/attendance">勤怠</a>
                    <a class="inner__group--item" href="/attendance/list">勤怠一覧</a>
                    <a class="inner__group--item" href="/stamp_correction_request/list">申請</a>
                    <button class="inner__group--item logout-button">
                        ログアウト
                    </button>
                </div>
            </form>
            @endif
        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>