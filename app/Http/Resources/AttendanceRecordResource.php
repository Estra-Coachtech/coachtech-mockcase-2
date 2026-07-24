<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * 一覧／詳細で同一 Resource を共用する。`whenLoaded()` により、関連データは
     * eager load されたとき（詳細API）のみレスポンスに含まれ、
     * 一覧API では自動的に省略される。
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            // API 仕様書／模範解答に合わせ、user 情報はネストオブジェクトではなく
            // user_name のフラットな文字列フィールドで返す
            'user_name' => $this->user?->name,
            'date' => $this->date instanceof \DateTimeInterface
                ? $this->date->format('Y-m-d')
                : $this->date,
            // API 仕様書に合わせ、時刻は H:i:s 文字列で返す（datetime キャストの ISO 文字列ではなく）
            'clock_in' => $this->clock_in instanceof \DateTimeInterface
                ? $this->clock_in->format('H:i:s')
                : $this->clock_in,
            'clock_out' => $this->clock_out instanceof \DateTimeInterface
                ? $this->clock_out->format('H:i:s')
                : $this->clock_out,
            'total_time' => $this->total_time,
            'total_break_time' => $this->total_break_time,
            'comment' => $this->comment,
            'breaks' => AttendanceBreakResource::collection($this->whenLoaded('breaks')),
            'applications' => ApplicationResource::collection($this->whenLoaded('applications')),
        ];
    }
}
