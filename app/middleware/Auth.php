<?php
declare (strict_types=1);

namespace app\middleware;

use app\model\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Cache;
use think\facade\Config;

class Auth
{
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return json_error('请先登录', 401);
        }

        try {
            $key = Config::get('jwt.key');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $userId = $decoded->user_id ?? 0;
            if (!$userId) {
                throw new \Exception('无效token');
            }

            // 检查token是否在黑名单（退出登录时加入）
            if (Cache::get('token_blacklist_' . $token)) {
                throw new \Exception('token已失效');
            }

            $user = User::find($userId);
            if (!$user || $user->status != 1) {
                throw new \Exception('用户不存在或被禁用');
            }

            // 将用户信息绑定到请求
            $request->userId = $userId;
            $request->user = $user;

        } catch (\Exception $e) {
            return json_error('认证失败：' . $e->getMessage(), 401);
        }

        return $next($request);
    }
}