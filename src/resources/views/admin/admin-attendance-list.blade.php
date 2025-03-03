@extends('layouts.admin-app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/admin-attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="content__header">
        <h2 class="content__header--item">{{ $date->format('Y年m月d日') }}の勤怠</h2>
    </div>
    <div class="content__menu">
        <a class="previous-day" href="?date={{ $previousDay }}">前日</a>
        <p class="current-day">{{ $date->format('Y/m/d') }}</p>
        @if ($date->lt(\Carbon\Carbon::create(2024, 12, 31)))
        <a class="next-day" href="?date={{ $nextDay }}">翌日</a>
        @else
        <div class="next-day-placeholder"></div>
        @endif
    </div>
    <table class="table">
        <tr class="table__row">
            <th class="table__header">
                <p class="table__header--item">名前</p>
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
        @foreach ($users as $user)
        @php
            $attendance = $attendanceRecords->where('user_id', $user->id)->first();
        @endphp
        @if ($attendance)
        <tr class="table__row">
            <td class="table__description">
                <p class="table__description--item">{{ $user->name }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendance->clock_in ? Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendance->clock_out ? Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendance->total_break_time ? Carbon\Carbon::parse($attendance->total_break_time)->format('H:i') : '' }}</p>
            </td>
            <td class="table__description">
                <p class="table__description--item">{{ $attendance->total_time ? Carbon\Carbon::parse($attendance->total_time)->format('H:i') : '' }}</p>
            </td>
            <td class="table__description">
                <a class="table__item--detail-link" href="{{ url('/attendance/' . $attendance['id']) }}">詳細</a>
            </td>
        </tr>
        @endif
        @endforeach
    </table>
</div>
@endsection
