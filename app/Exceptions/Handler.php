<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });

        // API リクエストに対して、エラーを統一的な JSON 形式で返す。
        // Laravel は prepareException で
        //  - ModelNotFoundException → NotFoundHttpException
        //  - AuthorizationException → AccessDeniedHttpException
        // に変換するため、変換後のクラスをハンドリングする。
        $this->renderable(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            // Route Model Binding 由来（ModelNotFoundException が起源）かを判定
            if ($e->getPrevious() instanceof ModelNotFoundException) {
                return response()->json(
                    ['error' => '勤怠情報が見つかりませんでした。'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                ['error' => 'エンドポイントが見つかりませんでした。'],
                JsonResponse::HTTP_NOT_FOUND
            );
        });

        $this->renderable(function (AccessDeniedHttpException $e, Request $request): ?JsonResponse {
            if ($request->is('api/*')) {
                return response()->json(
                    ['error' => 'この操作を実行する権限がありません。'],
                    JsonResponse::HTTP_FORBIDDEN
                );
            }

            return null;
        });

        $this->renderable(function (AuthenticationException $e, Request $request): ?JsonResponse {
            if ($request->is('api/*')) {
                return response()->json(
                    ['message' => 'Unauthenticated.'],
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }

            return null;
        });
    }
}
