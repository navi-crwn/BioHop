<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckIpBan;
use App\Http\Middleware\CheckSuspended;
use App\Http\Middleware\ContentSecurityPolicyMiddleware;
use App\Http\Middleware\RequireHopAdminClient;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VerifyHopSignature;
use App\Http\Middleware\VerifyHubRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            SetLocale::class,
            CheckSuspended::class,
            ContentSecurityPolicyMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: ['internal/*', 'hop/events']);
        // The language cookie is plain text (en, id, …), read before decryption.
        $middleware->encryptCookies(except: [SetLocale::COOKIE]);
        // `hop.auth` and `hop.token` come from hop/auth; `verified` is Laravel's (verification.notice goes to the hub).
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'ip.ban' => CheckIpBan::class,
            'hop.signed' => VerifyHopSignature::class,
            'hop.admin_client' => RequireHopAdminClient::class,
            'hub.signed' => VerifyHubRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An expired CSRF token (session timed out while the editor stayed open) is not worth a bare 419 page.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || ! $e->getPrevious() instanceof TokenMismatchException || $request->expectsJson()) {
                return null;
            }

            return redirect()->back()
                ->withInput($request->except(['_token', 'password']))
                ->with('session_expired', 'Your session expired. Please try again.');
        });
    })->create();
