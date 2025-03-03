@extends('layouts.admin-app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/admin-application-detail.css') }}">
@endsection

@section('content')
<div class="detail__content">
    <div class="detail__header">
        <h2 class="content__header--item">勤怠詳細</h2>
    </div>
    <form class="applied-form" action="{{ url('/stamp_correction_request/approve/' . $application['id']) }}" method="post">
        @csrf
        <div class="applied-form__content">
            <div class="applied-form__group">
                <p class="applied-form__header">名前</p>
                <div class="applied-form__input-group">
                    <input class="applied-form__input" type="text" name="name" value="{{ $user->name }}" readonly>
                </div>
            </div>
            <div class="applied-form__group">
                <p class="applied-form__header">日付</p>
                <div class="applied-form__input-group">
                    <input class="applied-form__input" type="text" value="{{ $application->new_date->format('Y年') }}" readonly>
                    <input class="applied-form__input"  type="text"  value="{{ $application->new_date->format('m月d日') }}" readonly>
                </div>
            </div>
            <div class="applied-form__group">
                <p class="applied-form__header">出勤・退勤</p>
                <div class="applied-form__input-group">
                    <input class="applied-form__input" type="text" value="{{ $application->new_clock_in }}" readonly>
                    <p class="wavy-line">〜</p>
                    <input class="applied-form__input" type="text" value="{{ $application->new_clock_out }}" readonly>
                </div>
            </div>
            <div class="applied-form__group">
                <p class="applied-form__header">休憩</p>
                <div class="applied-form__input-group">
                    <input class="applied-form__input" type="text" value="{{ $application->new_break_in }}" readonly>
                    <p class="wavy-line">〜</p>
                    <input class="applied-form__input" type="text" value="{{ $application->new_break_out }}" readonly>
                </div>
            </div>
            <div class="applied-form__group">
                <p class="applied-form__header">休憩2</p>
                <div class="applied-form__input-group">
                    <input class="applied-form__input" type="text" value="{{ $application->new_break2_in }}" readonly>
                    <p class="wavy-line">〜</p>
                    <input class="applied-form__input" type="text" value="{{ $application->new_break2_out }}" readonly>
                </div>
            </div>
            <div class="applied-form__group">
                <p class="applied-form__header">備考</p>
                <div class="applied-form__input-group">
                    <textarea class="applied-form__textarea" name="comment" id="" readonly>{{ $application->comment }}</textarea>
                </div>
            </div>
        </div>
        <div class="applied-form__button">
            @if ($application->approval_status === '承認待ち')
            <button class="applied-form__button--submit" type="submit">承認</button>
            @elseif ($application->approval_status === '承認済み')
            <p class="applied-form__item">承認済み</p>
            @endif
        </div>
    </form>
</div>
@endsection
