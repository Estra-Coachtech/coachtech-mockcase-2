@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendance-register.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <div class="attendance__status">
        <p class="attendance__status--item">{{ $user->attendance_status }}</p>
    </div>
    <form class="attendance__form" action="/attendance" method="post">
        @csrf
        <div class="current-date">
            <input class="current-date__item" type="text" value="{{ $formattedDate }}" readonly>
        </div>
        <div class="current-time">
            <input class="current-time__item" type="text" id="currentTime" value="{{ $formattedTime }}" readonly>
        </div>
        <div class="attendance__button">
            @if($user->attendance_status === '勤務外')
                <button class="attendance__button--submit--clock-in" type="submit" name="action" value="clock_in">出勤</button>
            @elseif($user->attendance_status === '出勤中')
                <button class="attendance__button--submit--clock-out" type="submit" name="action" value="clock_out">退勤</button>
                <button class="attendance__button--submit--break-in" type="submit" name="action" value="break_in">休憩入</button>
            @elseif($user->attendance_status === '休憩中')
                <button class="attendance__button--submit--break-out" type="submit" name="action" value="break_out">休憩戻</button>
            @elseif($user->attendance_status === '退勤済')
                <p class="attendance__message">お疲れ様でした。</p>
            @endif
        </div>
    </form>
</div>

<script>
    function updateTime() {
        const now = new Date();
        const options = { hour: '2-digit', minute: '2-digit', hour12: false };
        document.getElementById('currentTime').value = now.toLocaleTimeString('ja-JP', options);
    }
    setInterval(updateTime, 1000);
    updateTime();
</script>
@endsection