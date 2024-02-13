<?php

namespace App\Http\Middleware;

use App\Utils\LogUtil;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BeforeAfterActionLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        LogUtil::i($request->route()->getActionName(). " ========[BEGIN]========");
        LogUtil::i("リクエスト情報");
        LogUtil::i("メソッド：". $request->method());
        LogUtil::i("リクエストデータ: " . json_encode($request->all()));
        $response = $next($request);
        LogUtil::i($request->route()->getActionName(). " ========[END]==========");
        return $response;
    }
}
