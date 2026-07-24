@extends('layouts.app')

@section('css')
@vite('resources/css/user/register.css')
@endsection

@section('content')
<div class="register__content">
    <div class="register__heading">
        <h1 class="register__heading--item">会員登録</h1>
    </div>
    @if(session('message'))
    <div class="alert-success">
        {{ session('message') }}
    </div>
    @endif
    <form class="form" action="/register" method="post" novalidate>
        @csrf
        <div class="form__group">
            <label class="form__label" for="name">名前</label>
            <input class="form__input" id="name" type="text" name="name" value="{{ old('name') }}">
            <div class="form__error">
                @error('name')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <label class="form__label" for="email">メールアドレス</label>
            <input class="form__input" id="email" type="email" name="email" value="{{ old('email') }}">
            <div class="form__error">
                @error('email')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <label class="form__label" for="password">パスワード</label>
            <input class="form__input" id="password" type="password" name="password">
            <div class="form__error">
                @error('password')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <label class="form__label" for="password_confirmation">パスワード確認</label>
            <input class="form__input" id="password_confirmation" type="password" name="password_confirmation">
            <div class="form__error">
                @error('password_confirmation')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__button">
            <button class="form__button--submit">登録する</button>
        </div>
    </form>
    <div class="login__link">
        <a class="login__link--item" href="/login">ログインはこちら</a>
    </div>
</div>
@endsection