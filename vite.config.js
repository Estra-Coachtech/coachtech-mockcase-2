import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/admin/admin-application-detail.css',
                'resources/css/admin/admin-application-list.css',
                'resources/css/admin/admin-attendance-list.css',
                'resources/css/admin/admin-detail.css',
                'resources/css/admin/admin-login.css',
                'resources/css/admin/staff-attendance-list.css',
                'resources/css/admin/staff-list.css',
                'resources/css/auth/verify-email.css',
                'resources/css/common.css',
                'resources/css/reports/index.css',
                'resources/css/sanitize.css',
                'resources/css/user/attendance-register.css',
                'resources/css/user/register.css',
                'resources/css/user/user-application-list.css',
                'resources/css/user/user-attendance-list.css',
                'resources/css/user/user-detail.css',
                'resources/css/user/user-login.css',
            ],
            refresh: true,
        }),
    ],
});
