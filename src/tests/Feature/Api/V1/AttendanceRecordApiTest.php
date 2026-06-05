<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'admin_status' => false,
            'attendance_status' => '勤務外',
        ], $attrs));
    }

    private function createRecord(User $user, array $attrs = []): AttendanceRecord
    {
        return AttendanceRecord::create(array_merge([
            'user_id' => $user->id,
            'date' => '2026-05-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'テスト勤怠',
        ], $attrs));
    }

    /** @test */
    public function index_returns_200_with_data_and_meta(): void
    {
        $user = $this->createUser();
        $this->createRecord($user);
        $this->createRecord($user, ['date' => '2026-05-02']);

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'date', 'clock_in', 'clock_out'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    /** @test */
    public function index_filters_by_user_id(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();
        $this->createRecord($userA);
        $this->createRecord($userB, ['date' => '2026-05-03']);

        $response = $this->getJson('/api/v1/attendance-records?user_id='.$userA->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.user_id', $userA->id);
    }

    /** @test */
    public function index_respects_per_page_parameter(): void
    {
        $user = $this->createUser();
        foreach (range(1, 5) as $i) {
            $this->createRecord($user, ['date' => '2026-05-0'.$i]);
        }

        $response = $this->getJson('/api/v1/attendance-records?per_page=2');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function index_rejects_per_page_over_100(): void
    {
        $response = $this->getJson('/api/v1/attendance-records?per_page=200');

        $response->assertStatus(422);
    }

    /** @test */
    public function show_returns_200_with_record_detail(): void
    {
        $user = $this->createUser();
        $record = $this->createRecord($user);

        $response = $this->getJson('/api/v1/attendance-records/'.$record->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $record->id)
            ->assertJsonPath('data.user.id', $user->id)
            // 時刻は H:i:s 文字列で返る
            ->assertJsonPath('data.clock_in', '09:00:00')
            ->assertJsonPath('data.clock_out', '18:00:00')
            // user には email を含めず id / name のみ返す
            ->assertJsonPath('data.user', ['id' => $user->id, 'name' => $user->name]);
    }

    /** @test */
    public function show_returns_404_for_missing_id(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404)
            ->assertJsonPath('error', '勤怠情報が見つかりませんでした。');
    }

    /** @test */
    public function unauthenticated_write_returns_401(): void
    {
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-05-10',
            'clock_in' => '09:00:00',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    /** @test */
    public function authenticated_user_can_create_record(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-05-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'API登録',
        ]);

        $response->assertStatus(201)
            // total_time / total_break_time が算出されて返る（NULL のままにしない）
            ->assertJsonPath('data.total_break_time', '00:00')
            ->assertJsonPath('data.total_time', '09:00');
        // date / clock_in は MySQL/SQLite で保存形式が異なるため、Eloquent 経由で検証
        $record = AttendanceRecord::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($record);
        $this->assertSame('2026-05-15', $record->date->format('Y-m-d'));
        $this->assertSame('09:00', \Carbon\Carbon::parse($record->clock_in)->format('H:i'));
    }

    /** @test */
    public function invalid_data_returns_422_with_japanese_messages(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'clock_in' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date', 'clock_in']);
    }

    /** @test */
    public function owner_can_update_own_record(): void
    {
        $user = $this->createUser();
        $record = $this->createRecord($user);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/attendance-records/'.$record->id, [
            'comment' => '更新後コメント',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'comment' => '更新後コメント',
        ]);
    }

    /** @test */
    public function other_user_cannot_update_record_returns_403(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $record = $this->createRecord($owner);
        Sanctum::actingAs($other);

        $response = $this->putJson('/api/v1/attendance-records/'.$record->id, [
            'comment' => '不正な更新',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'この操作を実行する権限がありません。');
    }

    /** @test */
    public function admin_can_update_other_user_record(): void
    {
        $owner = $this->createUser();
        $admin = $this->createUser(['admin_status' => true]);
        $record = $this->createRecord($owner);
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/v1/attendance-records/'.$record->id, [
            'comment' => '管理者による更新',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function owner_can_delete_own_record(): void
    {
        $user = $this->createUser();
        $record = $this->createRecord($user);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/attendance-records/'.$record->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);
    }

    /** @test */
    public function other_user_cannot_delete_record_returns_403(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $record = $this->createRecord($owner);
        Sanctum::actingAs($other);

        $response = $this->deleteJson('/api/v1/attendance-records/'.$record->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('attendance_records', ['id' => $record->id]);
    }

    /** @test */
    public function delete_with_missing_id_returns_404(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404)
            ->assertJsonPath('error', '勤怠情報が見つかりませんでした。');
    }
}
