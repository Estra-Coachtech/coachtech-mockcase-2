@extends('layouts.app')

@section('css')
@vite('resources/css/reports/index.css')
@endsection

@section('content')
<div class="reports">
    <h1>マイ勤怠レポート</h1>
    <p>過去 6 ヶ月の勤怠データから集計しています。</p>

    {{-- 基本サマリー --}}
    <h2>基本サマリー</h2>
    <div class="summary">
        <div class="summary__card">
            <div class="summary__label">総労働時間</div>
            <div class="summary__value">{{ floor($summary['total_work_minutes'] / 60) }}h {{ $summary['total_work_minutes'] % 60 }}m</div>
        </div>
        <div class="summary__card">
            <div class="summary__label">総残業時間</div>
            <div class="summary__value">{{ floor($summary['total_overtime_minutes'] / 60) }}h {{ $summary['total_overtime_minutes'] % 60 }}m</div>
        </div>
        <div class="summary__card">
            <div class="summary__label">平均労働時間 / 日</div>
            <div class="summary__value">{{ floor($summary['avg_work_minutes'] / 60) }}h {{ $summary['avg_work_minutes'] % 60 }}m</div>
        </div>
    </div>

    {{-- 月次推移 --}}
    <h2>月次推移（過去 6 ヶ月）</h2>
    <table>
        <thead>
            <tr>
                <th>月</th>
                <th>労働時間</th>
                <th>残業時間</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyTrend as $row)
                <tr>
                    <td>{{ $row['month'] }}</td>
                    <td>{{ floor($row['work_minutes'] / 60) }}h {{ $row['work_minutes'] % 60 }}m</td>
                    <td>{{ floor($row['overtime_minutes'] / 60) }}h {{ $row['overtime_minutes'] % 60 }}m</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 異常検知（直近月） --}}
    <h2>今月の異常検知</h2>
    <p class="reports__note">基準: 始業 {{ '09:00' }} / 終業 {{ '18:00' }} / 長時間労働は 1 日 10 時間超</p>
    <div class="anomalies">
        <div class="summary__card">
            <div class="summary__label">遅刻回数</div>
            <div class="summary__value">{{ $anomalies['late_count'] }} 回</div>
        </div>
        <div class="summary__card">
            <div class="summary__label">早退回数</div>
            <div class="summary__value">{{ $anomalies['early_leave_count'] }} 回</div>
        </div>
        <div class="summary__card">
            <div class="summary__label">長時間労働日数</div>
            <div class="summary__value">{{ $anomalies['long_work_count'] }} 日</div>
        </div>
    </div>
</div>
@endsection
