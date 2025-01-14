<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Ownership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authId = Auth::id();
        $userId = $request->route()->originalParameter('user');

        throw_if(
            ($userId == null || $userId != $authId),
            AccessDeniedHttpException::class,
            'access denied'
        );

        return $next($request);
    }
}
