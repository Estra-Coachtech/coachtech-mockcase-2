<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト時は Vite のビルド成果物（manifest）を要求しないようにする。
        // （@vite ディレクティブを含むビューを描画してもエラーにならない）
        $this->withoutVite();
    }
}
