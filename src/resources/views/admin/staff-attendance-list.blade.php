@extends('layouts.admin-app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="content__header">
        <h2 class="content__header--item">{{ $user->name }}さんの勤怠</h2>
    </div>
    <div class="content__menu">
        <a class="previous-month" href="?date={{ $previousMonth }}">前月</a>
        <p class="current-month">{{ $date->format('Y/m') }}</p>
        @if ($date->lt(\Carbon\Carbon::create(2024, 12, 1)))
        <a class="next-month" href="?date={{ $nextMonth }}">翌月</a>
        @else
        <div class="next-month-placeholder"></div>
        @endif
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
        @foreach ($formattedAttendanceRecords as $attendanceRecords)
        <tr class="table__row">
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecords['date'] }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecords['clock_in'] }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecords['clock_out'] }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecords['total_break_time'] }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendanceRecords['total_time'] }}</p>
            </td>
            <td class="table__description">
                <a class="table__item--detail-link" href="{{ url('/attendance/' . $attendanceRecords['id']) }}">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>
    <div class="csv-button">
        <form action="/export" method="post">
        @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="year_month" value="{{ $date->format('Y-m') }}">
            <input class="csv-button__submit" type="submit" value="エクスポート">
        </form>
    </div>
</div>
@endsection
