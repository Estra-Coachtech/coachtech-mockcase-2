@extends('layouts.app')

@section('css')
@vite('resources/css/user/user-attendance-list.css')
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="content__header">
        <h1 class="content__header--item">勤怠一覧</h1>
    </div>
    <div class="content__menu">
        <a class="previous-month" href="?date={{ $previousMonth }}">前月</a>
        <p class="current-month">{{ $date->format('Y/m') }}</p>
        <a class="next-month" href="?date={{ $nextMonth }}">翌月</a>
    </div>
    <table class="table">
        <tr class="table__row">
            <th class="table__header">
                <p class="table__header--item">日付</p>
            </th>
            <th class="table__header">
                <p class="table__header--item">出勤</p>
            </th>
            <th class="table__header">
                <p class="table__header--item">退勤</p>
            </th>
            <th class="table__header">
                <p class="table__header--item">休憩</p>
            </th>
            <th class="table__header">
                <p class="table__header--item">合計</p>
            </th>
            <th class="table__header">
                <p class="table__header--item">詳細</p>
            </th>
        </tr>
        @foreach($formattedAttendanceRecords as $attendanceRecord)
        <tr class="table__row">
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecord['date'] }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecord['clock_in'] }}
                </p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecord['clock_out'] }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecord['total_break_time'] ? \Carbon\Carbon::parse($attendanceRecord['total_break_time'])->format('G:i') : '' }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecord['total_time'] ? \Carbon\Carbon::parse($attendanceRecord['total_time'])->format('G:i') : '' }}</p>
            </td>
            <td class="table__description">
                @if (!empty($attendanceRecord['id']))
                <a class="table__item--detail-link" href="{{ url('/attendance/' . $attendanceRecord['id']) }}">詳細</a>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
