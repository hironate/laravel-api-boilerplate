<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function App\Helpers\respond;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('api/user')->name('user.')->group(base_path('routes/user.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (\Throwable $e, Request $request) {

            if ($request->is('api/*') || $request->wantsJson()) {

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return respond('Unauthenticated.', null, 401);
                }

                if ($e instanceof \Laravel\Sanctum\Exceptions\MissingAbilityException) {
                    return respond('You do not have the required abilities for this action.', null, 403);
                }

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return respond('Validation failed', $e->errors(), 422);
                }

                if ($e instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
                    return respond('You do not have the required permissions for this action.', null, 403);
                }

                if ($e instanceof \Illuminate\Routing\Exceptions\InvalidSignatureException) {
                    return respond('Invalid signature.', null, 403);
                }
            }
        });
    })->create();
