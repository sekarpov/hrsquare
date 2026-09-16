<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: ['REMOTE_ADDR']);
        $middleware->redirectGuestsTo(fn () => '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
            }
        });
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $message = match ($e->getStatusCode()) {
                403 => 'Недостаточно прав.',404 => 'Не найдено.',419 => 'Сессия истекла. Обновите страницу.',429 => 'Слишком много запросов. Попробуйте позже.',default => $e->getMessage() ?: 'Ошибка запроса.'
            };

            return response()->json(['message' => $message], $e->getStatusCode(), $e->getHeaders());
        });
    })->create();
