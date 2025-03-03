# coachtech-mockcase-2
## 環境構築
Dockerビルド  
1.git clone git@github.com:coachtech-material/laravel-docker-template.git  
2.docker-compose up -d --build  

Lavaral環境構築  
1.docker-compose exec php bash  
2.composer install  
3.cp .env.example .env  
4..envファイルの変更  
```
　DB_HOSTをmysqlに変更  
　DB_DATABASEをlaravel_dbに変更  
　DB_USERNAMEをlaravel_userに変更  
　DB_PASSをlaravel_passに変更  
　MAIL_FROM_ADDRESSに送信元アドレスを設定  
```
5.php artisan key:generate  
6.php artisan migrate  
7.php artisan db:seed  
8.php artisan test  

9.メール認証用の設定
```
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=任意のメールアドレス
MAIL_FROM_NAME="${APP_NAME}"
```

### ログイン情報  
一般ユーザー  
　id：user1@example.com／user2@example.com  
　pass：password  
管理者  
　id：user3@example.com  
　pass：password  

## 使用技術
・PHP 7.4.9  
・Laravel 8.83.8  
・MySQL 8.0.26  
・nginx 1.21.1  
・MailHog latest  

### URL
・開発環境：http://localhost/  
・phpMyAdmin：http://localhost:8080/  
・MailHog：http://localhost:8025/
