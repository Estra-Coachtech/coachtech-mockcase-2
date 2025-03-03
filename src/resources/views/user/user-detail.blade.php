@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/user-detail.css') }}">
@endsection

@section('content')
<div class="detail__content">
    <div class="detail__header">
        <h2 class="content__header--item">勤怠詳細</h2>
    </div>
    <form class="form" action="{{ url('/attendance/' . $attendanceRecord['id']) }}" method="post">
        @csrf
        @if ($application->isEmpty())
        <div class="form__content">
            <div class="form__group">
                <p class="form__header">名前</p>
                <div class="form__input-group">
                    <input class="form__input form__input--name" type="text" name="name" value="{{ $user->name }}" readonly>
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">日付</p>
                <div class="form__input-group">
                    <input class="form__input" type="text" value="{{ $attendanceRecord['year'] }}">
                    <input class="form__input"  type="text" name="new_date" value="{{ $attendanceRecord['date'] }}">
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">出勤・退勤</p>
                <div class="form__input-group">
                    <input class="form__input" type="text" name="new_clock_in" value="{{ $attendanceRecord['clock_in'] }}">
                    <p>〜</p>
                    <input class="form__input" type="text" name="new_clock_out" value="{{ $attendanceRecord['clock_out'] }}">
                </div>
            </div>
            <div class="error-message">
                <div></div>
                <div class="error-message__item">
                    @error('new_clock_in')
                        {{ $message }}
                    @enderror
                    @error('new_clock_out')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">休憩</p>
                <div class="form__input-group">
                    <input class="form__input" type="text" name="new_break_in" value="{{ $attendanceRecord['break_in'] }}">
                    <p>〜</p>
                    <input class="form__input" type="text" name="new_break_out" value="{{ $attendanceRecord['break_out'] }}">
                </div>
            </div>
            <div class="error-message">
                <div></div>
                <div class="error-message__item">
                    @error('new_break_in')
                        {{ $message }}
                    @enderror
                    @error('new_break_out')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">休憩2</p>
                <div class="form__input-group">
                    <input class="form__input" type="text" name="new_break2_in" value="{{ $attendanceRecord['break2_in'] }}">
                    <p>〜</p>
                    <input class="form__input" type="text" name="new_break2_out" value="{{ $attendanceRecord['break2_out'] }}">
                </div>
            </div>
            <div class="error-message">
                <div></div>
                <div class="error-message__item">
                    @error('new_break2_in')
                        {{ $message }}
                    @enderror
                    @error('new_break2_out')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">備考</p>
                <div class="form__input-group">
                    <textarea class="form__textarea" name="comment" id="">{{ $attendanceRecord['comment'] }}</textarea>
                </div>
            </div>
            <div class="error-message">
                <div></div>
                <div class="error-message__item">
                    @error ('comment')
                        {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button--submit" type="submit">修正</button>
        </div>
        @elseif (!$application->isEmpty())
        <div class="form__content">
            <div class="form__group">
                <p class="form__header">名前</p>
                <div class="form__input-group">
                    <input class="form__input form__input--name readonly" type="text" name="name" value="{{ $user->name }}" readonly>
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">日付</p>
                <div class="form__input-group">
                    <input class="form__input readonly" type="text" value="{{ $attendanceRecord['year'] }}" readonly>
                    <input class="form__input readonly"  type="text" name="new_date" value="{{ $applicationData['new_date'] }}" readonly>
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">出勤・退勤</p>
                <div class="form__input-group">
                    <input class="form__input readonly" type="text" name="new_clock_in" value="{{ $applicationData['new_clock_in'] }}" readonly>
                    <p>〜</p>
                    <input class="form__input readonly" type="text" name="new_clock_out" value="{{ $applicationData['new_clock_out'] }}" readonly>
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">休憩</p>
                <div class="form__input-group">
                    <input class="form__input readonly" type="text" name="new_break_in" value="{{ $applicationData['new_break_in'] }}" readonly>
                    <p>〜</p>
                    <input class="form__input readonly" type="text" name="new_break_out" value="{{ $applicationData['new_break_out'] }}" readonly>
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">休憩2</p>
                <div class="form__input-group">
                    <input class="form__input readonly" type="text" name="new_break2_in" value="{{ $applicationData['new_break2_in'] }}" readonly>
                    <p>〜</p>
                    <input class="form__input readonly" type="text" name="new_break2_out" value="{{ $applicationData['new_break2_out'] }}" readonly>
                </div>
            </div>
            <div class="form__group">
                <p class="form__header">備考</p>
                <div class="form__input-group">
                    <textarea class="form__textarea readonly" name="comment" id="" readonly>{{ $applicationData['comment'] }}</textarea>
                </div>
            </div>
        </div>
        <div class="form__button">
            <p class="readonly-message">承認待ちのため修正できません</p>
        </div>
        @endif
    </form>
</div>
@endsection