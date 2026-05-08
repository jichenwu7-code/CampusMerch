<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ZhyController extends Controller
{
    // 发送邮箱验证码
    public function sendVerifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'scene' => 'required|in:register,login,reset_password'
        ]);

        $email = $request->email;
        $scene = $request->scene;

        // 频率限制：同一邮箱同一场景1分钟1次
        $rateKey = "verify_rate:{$email}:{$scene}";
        if (Cache::has($rateKey)) {
            return response()->json([
                'code' => 429,
                'message' => '发送过于频繁，请稍后再试'
            ], 429);
        }

        // 生成6位验证码，5分钟有效
        $code = random_int(100000, 999999);
        $ttl = 300;

        // 写入缓存并立即验证
        Cache::put("verify_code:{$email}:{$scene}", $code, now()->addSeconds($ttl));
        Cache::put($rateKey, true, now()->addMinute());

        // 调试日志（可选）
        \Log::info('验证码已写入', [
        'key' => "verify_code:{$email}:{$scene}",
        'code' => $code,
        'cached' => Cache::get("verify_code:{$email}:{$scene}")
    ]);

    // 发送邮件
    try {
        Mail::raw("您的验证码是：{$code}，有效期{$ttl}秒。", function ($message) use ($email) {
            $message->to($email)->subject('验证码');
        });
    } catch (\Exception $e) {
        return response()->json(['code' => 500, 'message' => '邮件发送失败，请检查配置'], 500);
    }

    // 每日剩余次数
    $dailyKey = "verify_daily:{$email}:" . now()->toDateString();
    $count = Cache::get($dailyKey, 0);
    Cache::put($dailyKey, $count + 1, now()->endOfDay());
    $remain = max(0, 5 - ($count + 1));

    return response()->json([
        'code' => 200,
        'message' => "验证码已发送至邮箱{$email}，请注意查收",
        'data' => [
            'email' => $email,
            'expire_time' => $ttl,
            'remain_count' => $remain
        ]
    ]);
}

    // 注册
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'verify_code' => 'required|digits:6'
        ]);

        // 校验验证码
        $codeKey = "verify_code:{$request->email}:register";
        $cached = Cache::get($codeKey);
        if (!$cached || $cached != $request->verify_code) {
            return response()->json(['code' => 422, 'message' => '验证码错误或已过期'], 422);
        }

        // 创建用户（密码自动哈希）
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        Cache::forget($codeKey);

        return response()->json([
            'code' => 200,
            'message' => '注册成功',
            'data' => [
                'user_id' => $user->id,
                'role' => $user->role
            ]
        ]);
    }

    // 登录
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['code' => 401, 'message' => '邮箱或密码错误'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user_id' => $user->id,
                'role' => $user->role
            ]
        ]);
    }

    // 验证码登录
    public function loginByCode(Request $request)
    {
        $request->validate([
            'email'      => 'required|email',
            'verify_code' => 'required|digits:6'
        ]);

        $email = $request->email;
        $code  = $request->verify_code;

        // 1. 校验验证码
        $codeKey = "verify_code:{$email}:login";
        $cached  = Cache::get($codeKey);

        if (!$cached || $cached != $code) {
            return response()->json([
                'code'    => 422,
                'message' => '验证码错误或已过期'
            ], 422);
        }

        // 2. 查找用户（不允许自动注册）
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'code'    => 404,
                'message' => '该邮箱未注册'
            ], 404);
        }

        // 3. 验证通过，删除验证码（防止重用）
        Cache::forget($codeKey);

        // 4. 生成 token 并返回
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'code'    => 200,
            'message' => '登录成功',
            'data'    => [
                'token'   => $token,
                'user_id' => $user->id,
                'role'    => $user->role,
            ]
        ]);
    }

    // 校验验证码（独立接口）
    public function verifyCodeCheck(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verify_code' => 'required|digits:6',
            'scene' => 'required|in:register,login,reset_password'
        ]);

        $codeKey = "verify_code:{$request->email}:{$request->scene}";
        $cached = Cache::get($codeKey);
        $valid = $cached && $cached == $request->verify_code;

        return response()->json([
            'code' => 200,
            'message' => $valid ? '验证码校验成功' : '验证码错误',
            'data' => [
                'email' => $request->email,
                'scene' => $request->scene,
                'valid' => $valid
            ]
        ]);
    }

    // 密码重置
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verify_code' => 'required|digits:6',
            'new_password' => 'required|string|min:6'
        ]);

        // 校验验证码
        $codeKey = "verify_code:{$request->email}:reset_password";
        $cached = Cache::get($codeKey);
        if (!$cached || $cached != $request->verify_code) {
            return response()->json(['code' => 422, 'message' => '验证码错误或已过期'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['code' => 404, 'message' => '该邮箱未注册'], 404);
        }

        $user->update(['password' => $request->new_password]);
        Cache::forget($codeKey);

        return response()->json([
            'code' => 200,
            'message' => '密码重置成功',
            'data' => []
        ]);
    }

    // 登出
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'code' => 200,
            'message' => '登出成功',
            'data' => []
        ]);
    }

    // 修改个人信息（需登录）
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:50',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:255'
        ]);

        $user = $request->user();
        $user->update([
            'name' => $request->name ?? $user->name,
            'mobile' => $request->phone ?? $user->mobile,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '修改成功',
            'data' => [
                'user_id' => $user->id,
                'update_time' => $user->updated_at->toDateTimeString()
            ]
        ]);
    }
}
