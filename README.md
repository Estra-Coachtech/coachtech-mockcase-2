# coachtech-mockcase-2（勤怠管理アプリ / 模範解答）

勤怠打刻・勤怠一覧・修正申請と承認・管理者機能・メール認証・公開API（Sanctum）を備えた勤怠管理アプリケーションです。

## 使用技術

- PHP 8.2
- Laravel 10.x
- MySQL 8.4
- Laravel Sail（Docker）
- Vite（アセットビルド）
- Mailpit（メール受信確認）

## 環境構築

本プロジェクトは **Laravel Sail**（Docker）で構築します。ローカルに PHP / Composer が無くても構築できます。

### 1. リポジトリのクローン

```bash
git clone git@github.com:Estra-Coachtech/coachtech-mockcase-2.git
cd coachtech-mockcase-2
```

### 2. 環境変数ファイルの作成

```bash
cp .env.example .env
```

### 3. Composer 依存のインストール（Docker 経由）

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install
```

### 4. Sail の起動

```bash
./vendor/bin/sail up -d
```

> 毎回 `./vendor/bin/sail` と打つ代わりに、エイリアスの設定を推奨します。
> ```bash
> alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
> ```

### 5. アプリケーションキーの生成

```bash
sail artisan key:generate
```

### 6. マイグレーション & シーディング

```bash
sail artisan migrate --seed
```

### 7. フロントエンドアセットのビルド

```bash
sail npm install
sail npm run build   # 開発中にホットリロードする場合は sail npm run dev
```

### 8. アクセス

- アプリ：http://localhost

## テスト実行

```bash
sail artisan test
```

## テーブル仕様

### users テーブル

| カラム名          | 型           | primary key | unique key | not null | foreign key |
| ----------------- | ------------ | ----------- | ---------- | -------- | ----------- |
| id                | bigint       | ◯           |            | ◯        |             |
| name              | varchar(255) |             |            | ◯        |             |
| email             | varchar(255) |             | ◯          | ◯        |             |
| email_verified_at | timestamp    |             |            |          |             |
| password          | varchar(255) |             |            | ◯        |             |
| remember_token    | varchar(100) |             |            |          |             |
| created_at        | timestamp    |             |            |          |             |
| updated_at        | timestamp    |             |            |          |             |
| admin_status      | tinyint      |             |            | ◯        |             |
| attendance_status | varchar(255) |             |            | ◯        |             |

### attendance_records テーブル

| カラム名         | 型           | primary key | unique key | not null | foreign key |
| ---------------- | ------------ | ----------- | ---------- | -------- | ----------- |
| id               | bigint       | ◯           |            | ◯        |             |
| user_id          | bigint       |             |            | ◯        | users(id)   |
| date             | date         |             |            | ◯        |             |
| clock_in         | time         |             |            | ◯        |             |
| clock_out        | time         |             |            |          |             |
| total_time       | varchar(255) |             |            |          |             |
| total_break_time | varchar(255) |             |            |          |             |
| comment          | varchar(255) |             |            |          |             |
| created_at       | timestamp    |             |            |          |             |
| updated_at       | timestamp    |             |            |          |             |

### breaks テーブル

| カラム名             | 型        | primary key | unique key | not null | foreign key            |
| -------------------- | --------- | ----------- | ---------- | -------- | ---------------------- |
| id                   | bigint    | ◯           |            | ◯        |                        |
| attendance_record_id | bigint    |             |            | ◯        | attendance_records(id) |
| break_in             | time      |             |            | ◯        |                        |
| break_out            | time      |             |            |          |                        |
| created_at           | timestamp |             |            |          |                        |
| updated_at           | timestamp |             |            |          |                        |

### applications テーブル

| カラム名             | 型           | primary key | unique key | not null | foreign key            |
| -------------------- | ------------ | ----------- | ---------- | -------- | ---------------------- |
| id                   | bigint       | ◯           |            | ◯        |                        |
| user_id              | bigint       |             |            | ◯        | users(id)              |
| attendance_record_id | bigint       |             |            | ◯        | attendance_records(id) |
| approval_status      | varchar(255) |             |            | ◯        |                        |
| application_date     | date         |             |            | ◯        |                        |
| new_date             | date         |             |            | ◯        |                        |
| new_clock_in         | time         |             |            | ◯        |                        |
| new_clock_out        | time         |             |            | ◯        |                        |
| comment              | varchar(255) |             |            | ◯        |                        |
| created_at           | timestamp    |             |            |          |                        |
| updated_at           | timestamp    |             |            |          |                        |

### application_breaks テーブル

| カラム名       | 型        | primary key | unique key | not null | foreign key      |
| -------------- | --------- | ----------- | ---------- | -------- | ---------------- |
| id             | bigint    | ◯           |            | ◯        |                  |
| application_id | bigint    |             |            | ◯        | applications(id) |
| break_in       | time      |             |            | ◯        |                  |
| break_out      | time      |             |            |          |                  |
| created_at     | timestamp |             |            |          |                  |
| updated_at     | timestamp |             |            |          |                  |

## ER 図

```mermaid
erDiagram
    users ||--o{ attendance_records : has
    users ||--o{ applications : submits
    attendance_records ||--o{ breaks : has
    attendance_records ||--o{ applications : targets
    applications ||--o{ application_breaks : has

    users {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at
        varchar password
        varchar remember_token
        tinyint admin_status
        varchar attendance_status
    }
    attendance_records {
        bigint id PK
        bigint user_id FK
        date date
        time clock_in
        time clock_out
        varchar total_time
        varchar total_break_time
        varchar comment
    }
    breaks {
        bigint id PK
        bigint attendance_record_id FK
        time break_in
        time break_out
    }
    applications {
        bigint id PK
        bigint user_id FK
        bigint attendance_record_id FK
        varchar approval_status
        date application_date
        date new_date
        time new_clock_in
        time new_clock_out
        varchar comment
    }
    application_breaks {
        bigint id PK
        bigint application_id FK
        time break_in
        time break_out
    }
```

## API エンドポイント一覧（応用）

公開 API v1。読み取り系（GET）は認証不要、書き込み系（POST / PUT / DELETE）は Sanctum 認証必須で、PUT / DELETE は本人または管理者のみ操作可能。

| HTTPメソッド | URI | 説明 | 認証・認可 |
| ------------ | --------------------------------------------- | ------------------ | ----------------------------------------- |
| GET | /api/v1/attendance-records | 勤怠一覧を取得する | 不要 |
| GET | /api/v1/attendance-records/{attendanceRecord} | 勤怠詳細を取得する | 不要 |
| POST | /api/v1/attendance-records | 勤怠を新規登録する | Sanctum 必須 |
| PUT / PATCH | /api/v1/attendance-records/{attendanceRecord} | 勤怠を更新する | Sanctum + Policy（本人または管理者のみ） |
| DELETE | /api/v1/attendance-records/{attendanceRecord} | 勤怠を削除する | Sanctum + Policy（本人または管理者のみ） |

## ログイン情報

一般ユーザー  
　 id：user1@example.com／user2@example.com  
　 pass：password  
管理者  
　 id：user3@example.com  
　 pass：password

## URL

・開発環境：http://localhost/  
・phpMyAdmin：http://localhost:8080/  
・Mailpit：http://localhost:8025/

## 作成者

COACHTECH（模範解答）
