<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール認証</title>
    @vite(['resources/css/sanitize.css', 'resources/css/common.css', 'resources/css/auth/verify-email.css'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
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
        <div class="verify__content">
            @if (session('message'))
                <div class="verify__flash">{{ session('message') }}</div>
            @endif

            <p class="verify__text">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <div class="verify__button">
                <a class="verify__link" href="http://localhost:8025" target="_blank" rel="noopener noreferrer">認証はこちらから</a>
            </div>

            <form class="verify__resend" action="{{ route('verification.send') }}" method="post">
                @csrf
                <button class="verify__resend-button" type="submit">認証メールを再送する</button>
            </form>
        </div>
    </main>
</body>

</html>
