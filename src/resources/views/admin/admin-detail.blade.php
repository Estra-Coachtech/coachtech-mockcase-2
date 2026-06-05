@extends('layouts.admin-app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/admin-detail.css') }}">
@endsection

@section('content')
    <div class="detail__content">
        <div class="detail__header">
            <h1 class="content__header--item">勤怠詳細</h1>
        </div>
        <form class="form" action="{{ url('/attendance/' . $attendanceRecord['id']) }}" method="post">
            @csrf
                <div class="form__content">
                    <div class="form__group">
                        <p class="form__header">名前</p>
                        <div class="form__input-group">
                            <input class="form__input form__input--name" type="text" name="name" value="{{ $user->name }}"
                                readonly>
                        </div>
                    </div>
                    <div class="form__group">
                        <p class="form__header">日付</p>
                        <div class="form__input-group">
                            {{-- 日付は表示のみ（修正不可） --}}
                            <input class="form__input" type="text" value="{{ $attendanceRecord['year'] }}" readonly>
                            <input class="form__input" type="text" name="new_date" value="{{ $attendanceRecord['date'] }}" readonly>
                        </div>
                    </div>

                    <div class="form__group">
                        <p class="form__header">出勤・退勤</p>
                        <div class="form__input-group">
                            <input class="form__input" type="text" name="new_clock_in"
                                value="{{ $attendanceRecord['clock_in'] }}">
                            <p>〜</p>
                            <input class="form__input" type="text" name="new_clock_out"
                                value="{{ $attendanceRecord['clock_out'] }}">
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

                    {{-- 休憩は「休憩」「休憩1」「休憩2」…とセクションを分けて表示 --}}
                    @php
                        $breaks = (isset($attendanceRecord['breaks']) && is_array($attendanceRecord['breaks']))
                            ? $attendanceRecord['breaks'] : [];
                    @endphp
                    @foreach($breaks as $index => $break)
                        <div class="form__group">
                            <p class="form__header">{{ $index === 0 ? '休憩' : '休憩' . $index }}</p>
                            <div class="form__input-group">
                                <input class="form__input" type="text" name="new_break_in[]"
                                    value="{{ $break['break_in'] ?? '' }}">
                                <p>〜</p>
                                <input class="form__input" type="text" name="new_break_out[]"
                                    value="{{ $break['break_out'] ?? '' }}">
                            </div>
                        </div>
                    @endforeach
                    {{-- 新しい休憩を追加できるよう末尾に空欄を1つ用意 --}}
                    <div class="form__group">
                        <p class="form__header">{{ count($breaks) === 0 ? '休憩' : '休憩' . count($breaks) }}</p>
                        <div class="form__input-group">
                            <input class="form__input" type="text" name="new_break_in[]" value="">
                            <p>〜</p>
                            <input class="form__input" type="text" name="new_break_out[]" value="">
                        </div>
                    </div>


                    <div class="error-message">
                        <div></div>
                        <div class="error-message__item">
                            @if($errors->has('new_break_in'))
                                @foreach ($errors->get('new_break_in') as $message)
                                    <p>{{ $message }}</p>
                                @endforeach
                            @endif

                            @if($errors->has('new_break_out'))
                                @foreach ($errors->get('new_break_out') as $message)
                                    <p>{{ $message }}</p>
                                @endforeach
                            @endif
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
        </form>
    </div>
@endsection
