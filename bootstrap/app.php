<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Seo;
use App\Support\TrailStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // The only authenticated area is the admin panel, so guests are sent
        // to its login screen and signed-in users are sent to its dashboard.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * Errors render through Inertia so a visitor who hits one still gets
         * the site around it rather than a bare white page. Only the statuses
         * a visitor can actually cause are handled; everything else falls
         * through to Laravel, and in local development the debug page is far
         * more use than a nicely worded apology.
         */
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->is('assets/*') || $request->expectsJson()) {
                return $response;
            }

            if (app()->hasDebugModeEnabled() && ! in_array($response->getStatusCode(), [404, 403], true)) {
                return $response;
            }

            if (! in_array($response->getStatusCode(), [401, 403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            $status = TrailStatus::for($response->getStatusCode());

            return Inertia::render('Error', [
                'seo' => Seo::make(
                    title: $status['sign'],
                    description: $status['body'],
                    index: false,
                ),
                'status' => $status,
            ])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        });
    })->create();
