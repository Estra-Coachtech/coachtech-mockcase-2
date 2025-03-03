@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/user-application-list.css') }}">
@endsection

@section('content')
<div class="application-list__content">
    <div class="content__header">
        <h2 class="content__header--item">申請一覧</h2>
    </div>
    <div class="application__tab">
        <input class="application__tab--input" id="tab1" type="radio" name="tab_item" checked>
        <label class="application__tab--label" for="tab1">承認待ち</label>
        <input class="application__tab--input" id="tab2" type="radio" name="tab_item">
        <label class="application__tab--label" for="tab2">承認済み</label>
        <div class="tab__content" id="content1">
            <table class="table">
                <tr class="table__row">
                    <th class="table__header">
                        <p class="table__header--item">状態</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">名前</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">対象日時</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">申請理由</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">申請日時</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">詳細</p>
                    </th>
                </tr>
                @foreach ($applications as $application)
                @if ($application->approval_status === '承認待ち')
                <tr class="table__row">
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->approval_status }}</p>
                    </td>
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->user->name }}</p>
                    </td>
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->AttendanceRecord->date->format('Y-m-d') }}</p>
                    </td>
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->comment }}</p>
                    </td>
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->application_date }}</p>
                    </td>
                    <td class="table__description">
                        <a class="table__item--detail-link" href="{{ url('/attendance/' . $application->attendance_record_id) }}">詳細</a>
                    </td>
                </tr>
                @endif
                @endforeach
            </table>
        </div>
        <div class="tab__content" id="content2">
            <table class="table">
                <tr class="table__row">
                    <th class="table__header">
                        <p class="table__header--item">状態</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">名前</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">対象日時</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">申請理由</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">申請日時</p>
                    </th>
                    <th class="table__header">
                        <p class="table__header--item">詳細</p>
                    </th>
                </tr>
                @foreach ($applications as $application)
                @if ($application->approval_status === '承認済み')
                <tr class="table__row">
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->approval_status }}</p>
                    </td>
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->user->name }}</p>
                    </td>
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->AttendanceRecord->date->format('Y-m-d') }}</p>
                    </td>
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->comment }}</p>
                    </td>
                    <td class="table__description">
                        <p class="table__description--item">{{ $application->application_date }}</p>
                    </td>
                    <td class="table__description">
                        <a class="table__item--detail-link" href="{{ url('/attendance/' . $application->attendance_record_id) }}">詳細</a>
                    </td>
                </tr>
                @endif
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection
