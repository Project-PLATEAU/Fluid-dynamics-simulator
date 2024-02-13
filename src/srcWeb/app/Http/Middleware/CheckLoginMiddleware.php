<?php

namespace App\Http\Middleware;

use App\Commons\Constants;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class CheckLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ログインせずに直接URLアクセスすると、強制的にログイン画面を求める。
        if (!Cookie::has(Constants::LOGIN_COOKIE_NAME)) {
            // ログイン画面に遷移
            return redirect()->route('login.index');
        }
        return $next($request);
    }
}
