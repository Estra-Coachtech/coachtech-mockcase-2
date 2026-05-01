@extends('layouts.app')

@section('css')
<style>
    .reports {
        max-width: 960px;
        margin: 32px auto;
        padding: 0 16px;
    }
    .reports h2 {
        margin-bottom: 16px;
        font-size: 20px;
    }
    .reports h3 {
        margin: 24px 0 12px;
        font-size: 16px;
    }
    .summary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    .summary__card {
        padding: 16px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background: #fafafa;
    }
    .summary__label {
        font-size: 12px;
        color: #666;
    }
    .summary__value {
        margin-top: 6px;
        font-size: 22px;
        font-weight: bold;
    }
    .reports table {
        width: 100%;
        border-collapse: collapse;
    }
    .reports th, .reports td {
        padding: 8px 12px;
        border-bottom: 1px solid #eee;
        text-align: right;
    }
    .reports th:first-child, .reports td:first-child {
        text-align: left;
    }
    .anomalies {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
</style>
@endsection

@section('content')
<div class="reports">
    <h2>マイ勤怠レポート</h2>
    <p>過去 6 ヶ月の勤怠データから集計しています。</p>

    {{-- 基本サマリー --}}
    <h3>基本サマリー</h3>
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
    <h3>月次推移（過去 6 ヶ月）</h3>
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
    <h3>今月の異常検知</h3>
    <p style="font-size: 12px; color: #666;">基準: 始業 {{ '09:00' }} / 終業 {{ '18:00' }} / 長時間労働は 1 日 10 時間超</p>
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
