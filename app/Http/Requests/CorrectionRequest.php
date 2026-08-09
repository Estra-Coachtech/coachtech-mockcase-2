<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => 'required|date_format:H:i',
            'new_clock_out' => 'required|date_format:H:i|after:new_clock_in',

            'new_break_in' => 'nullable|array',
            'new_break_out' => 'nullable|array',

            // 各要素が出勤後・退勤前であることをチェック
            'new_break_in.*' => 'nullable|date_format:H:i|after:new_clock_in|before:new_clock_out',
            'new_break_out.*' => 'nullable|date_format:H:i|after:new_clock_in|before:new_clock_out',

            'comment' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間を入力してください。',
            'new_clock_in.date_format' => '出勤時間は「HH:mm」形式で入力してください。',
            'new_clock_out.required' => '退勤時間を入力してください。',
            'new_clock_out.date_format' => '退勤時間は「HH:mm」形式で入力してください。',
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です。',

            'new_break_in.*.date_format' => '休憩開始は「HH:mm」形式で入力してください。',
            'new_break_in.*.before' => '休憩時間が不適切な値です。',
            'new_break_in.*.after' => '休憩時間が不適切な値です。',

            'new_break_out.*.date_format' => '休憩終了は「HH:mm」形式で入力してください。',
            'new_break_out.*.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'new_break_out.*.after' => '休憩時間もしくは退勤時間が不適切な値です',

            'comment.required' => '備考を記入してください。',
        ];
    }
}
