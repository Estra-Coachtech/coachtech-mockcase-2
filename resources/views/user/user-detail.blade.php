@extends('layouts.app')

@section('css')
    @vite('resources/css/user/user-detail.css')
@endsection

@section('content')
    <div class="detail__content">
        <div class="detail__header">
            <h1 class="content__header--item">勤怠詳細</h1>
        </div>
        <form class="form" action="{{ url('/attendance/' . $data['id']) }}" method="post">
            @csrf

            @if (is_null($data['application']))
                {{-- 承認待ちが無い場合：修正フォーム --}}
                <div class="form__content">
                    <div class="form__group">
                        <label class="form__header" for="name">名前</label>
                        <div class="form__input-group">
                            <input class="form__input form__input--name" id="name" type="text" name="name" value="{{ $user->name }}"
                                readonly>
                        </div>
                    </div>

                    <div class="form__group">
                        <label class="form__header">日付</label>
                        <div class="form__input-group">
                            {{-- 日付は表示のみ（修正不可）。名前欄と同様に枠線（border）を外す --}}
                            <input class="form__input form__input--date" type="text" value="{{ $data['year'] }}" readonly>
                            <input class="form__input form__input--date" type="text" name="new_date" value="{{ $data['date'] }}" readonly>
                        </div>
                    </div>

                    <div class="form__group">
                        <label class="form__header" for="new_clock_in">出勤・退勤</label>
                        <div class="form__input-group">
                            <input class="form__input" id="new_clock_in" type="text" name="new_clock_in" value="{{ $data['clock_in'] }}">
                            <p>〜</p>
                            <input class="form__input" type="text" name="new_clock_out" value="{{ $data['clock_out'] }}">
                        </div>
                    </div>

                    <div class="error-message">
                        <div></div>
                        <div class="error-message__item">
                            @error('new_clock_in') {{ $message }} @enderror
                            @error('new_clock_out') {{ $message }} @enderror
                        </div>
                    </div>

                    {{-- 休憩は「休憩」「休憩2」…とセクション分け表示。エラーは各行の入力欄の直下に個別表示する --}}
                    @foreach($data['breaks'] as $index => $break)
                        <div class="form__group">
                            <label class="form__header">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</label>
                            <div class="form__input-group">
                                <input class="form__input" type="text" name="new_break_in[{{ $index }}]" value="{{ $break['break_in'] }}">
                                <p>〜</p>
                                <input class="form__input" type="text" name="new_break_out[{{ $index }}]" value="{{ $break['break_out'] }}">
                            </div>
                        </div>
                        <div class="error-message">
                            <div></div>
                            <div class="error-message__item">
                                @error('new_break_in.' . $index)<p>{{ $message }}</p>@enderror
                                @error('new_break_out.' . $index)<p>{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endforeach
                    {{-- 新しい休憩を追加できるよう末尾に空欄を1つ用意 --}}
                    @php $newBreakIndex = count($data['breaks']); @endphp
                    <div class="form__group">
                        <label class="form__header">{{ $newBreakIndex === 0 ? '休憩' : '休憩' . ($newBreakIndex + 1) }}</label>
                        <div class="form__input-group">
                            <input class="form__input" type="text" name="new_break_in[{{ $newBreakIndex }}]" value="">
                            <p>〜</p>
                            <input class="form__input" type="text" name="new_break_out[{{ $newBreakIndex }}]" value="">
                        </div>
                    </div>
                    <div class="error-message">
                        <div></div>
                        <div class="error-message__item">
                            @error('new_break_in.' . $newBreakIndex)<p>{{ $message }}</p>@enderror
                            @error('new_break_out.' . $newBreakIndex)<p>{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form__group">
                        <label class="form__header" for="comment">備考</label>
                        <div class="form__input-group">
                            <input class="form__textarea" id="comment" name="comment" value="{{ $data['comment'] }}">
                        </div>
                    </div>

                    <div class="error-message">
                        <div></div>
                        <div class="error-message__item">
                            @error('comment') {{ $message }} @enderror
                        </div>
                    </div>
                </div>

                <div class="form__button">
                    <button class="form__button--submit" type="submit">修正</button>
                </div>

            @else
                {{-- 承認待ちあり：閲覧のみ --}}
                <div class="form__content">
                    <div class="form__group">
                        <label class="form__header">名前</label>
                        <div class="form__input-group">
                            <input class="form__input form__input--name readonly" type="text" value="{{ $user->name }}"
                                readonly>
                        </div>
                    </div>

                    <div class="form__group">
                        <label class="form__header">日付</label>
                        <div class="form__input-group">
                            <input class="form__input readonly" type="text" value="{{ $data['year'] }}" readonly>
                            <input class="form__input readonly" type="text" value="{{ $data['date'] }}" readonly>
                        </div>
                    </div>

                    <div class="form__group">
                        <label class="form__header">出勤・退勤</label>
                        <div class="form__input-group">
                            <input class="form__input readonly" type="text" value="{{ $data['clock_in'] }}" readonly>
                            <p>〜</p>
                            <input class="form__input readonly" type="text" value="{{ $data['clock_out'] }}" readonly>
                        </div>
                    </div>

                    {{-- 休憩は「休憩」「休憩2」「休憩3」…とセクションを分けて表示 --}}
                    @foreach($data['breaks'] as $index => $break)
                        <div class="form__group">
                            <label class="form__header">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</label>
                            <div class="form__input-group">
                                <input class="form__input readonly" type="text" name="new_break_in[]"
                                    value="{{ $break['break_in'] }}" readonly>
                                <p>〜</p>
                                <input class="form__input readonly" type="text" name="new_break_out[]"
                                    value="{{ $break['break_out'] }}" readonly>
                            </div>
                        </div>
                    @endforeach

                    <div class="form__group">
                        <label class="form__header">備考</label>
                        <div class="form__input-group">
                            <input class="form__textarea readonly" name="comment" value="{{ $data['comment'] }}"
                                readonly></input>
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
