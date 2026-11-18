<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->banned_at && ! $user->is_admin) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '账号已停用，如有疑问请联系管理员。',
                    'code' => 'ACCOUNT_BANNED',
                ], 403);
            }

            abort(403, '账号已停用');
        }

        return $next($request);
    }
}