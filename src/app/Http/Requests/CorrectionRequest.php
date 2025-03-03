<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'new_clock_in' => 'required|date_format:H:i',
            'new_clock_out' => 'required|date_format:H:i|after:new_clock_in',
            'new_break_in' => 'nullable|date_format:H:i|after:new_clock_in|before:new_break_out',
            'new_break_out' => 'nullable|date_format:H:i|after:new_break_in|before:new_clock_out',
            'new_break2_in' => 'nullable|date_format:H:i|after:new_clock_in|before:new_break2_out',
            'new_break2_out' => 'nullable|date_format:H:i|after:new_break2_in|before:new_clock_out',
            'comment' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'new_clock_in.required' => '出勤時間を入力してください。',
            'new_clock_out.required' => '退勤時間を入力してください。',
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です。',
            'new_break_in.before' => '休憩時間が勤務時間外です。',
            'new_break_in.after' => '休憩時間が勤務時間外です。',
            'new_break_out.before' => '休憩時間が勤務時間外です。',
            'new_break_out.after' => '休憩時間が勤務時間外です。',
            'new_break2_in.before' => '休憩時間が勤務時間外です。',
            'new_break2_in.after' => '休憩時間が勤務時間外です。',
            'new_break2_out.before' => '休憩時間が勤務時間外です。',
            'new_break2_out.after' => '休憩時間が勤務時間外です。',
            'comment.required' => '備考を記入してください。'
        ];
    }
}
