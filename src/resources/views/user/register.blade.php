@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/register.css') }}">
@endsection

@section('content')
<div class="register__content">
    <div class="register__heading">
        <h2 class="register__heading--item">会員登録</h2>
    </div>
    @if(session('message'))
    <div class="alert-success">
        {{ session('message') }}
    </div>
    @endif
    <form class="form" action="/register" method="post">
        @csrf
        <div class="form__group">
            <span class="form__label">名前</span>
            <input class="form__input" type="text" name="name" value="{{ old('name') }}">
            <div class="form__error">
                @error('name')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <span class="form__label">メールアドレス</span>
            <input class="form__input" type="email" name="email" value="{{ old('email') }}">
            <div class="form__error">
                @error('email')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <span class="form__label">パスワード</span>
            <input class="form__input" type="password" name="password">
            <div class="form__error">
                @error('password')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <span class="form__label">パスワード確認</span>
            <input class="form__input" type="password" name="password_confirmation">
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