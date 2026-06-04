<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): Response
    {
        if ($request instanceof Request && $request->is('api/*')) {
            $response = parent::render($request, $e);
            $status = $e instanceof ValidationException ? 422 : $response->getStatusCode();
            $message = $status >= 500 && ! config('app.debug') ? 'Terjadi kesalahan pada server' : $e->getMessage();

            Log::error('[QHealth API] Request failed', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $status,
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $message ?: 'API request failed',
                'data' => $e instanceof ValidationException ? ['errors' => $e->errors()] : null,
            ], $status);
        }

        return parent::render($request, $e);
    }
}
