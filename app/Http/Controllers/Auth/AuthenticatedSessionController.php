<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserLoginLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['邮箱或密码不正确。'],
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->isBanned()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => '账号已停用，如有疑问请联系管理员。',
                'code' => 'ACCOUNT_BANNED',
            ], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => '请先完成邮箱验证。',
                'code' => 'EMAIL_NOT_VERIFIED',
            ], 403);
        }

        if (! $user->is_admin) {
            UserLoginLog::query()->create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'logged_in_at' => now(),
            ]);
        }

        return response()->json(['message' => '登录成功']);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => '已退出登录']);
    }
}