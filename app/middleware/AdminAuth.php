<?php
declare (strict_types=1);

namespace app\middleware;

use app\model\Admin;
use think\facade\Cache;
use think\facade\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AdminAuth
{
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Admin-Token');
        if (!$token) {
            return json_error('请先登录', 401);
        }

        try {
            $key = Config::get('jwt.key');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $adminId = $decoded->admin_id ?? 0;
            if (!$adminId) {
                throw new \Exception('无效token');
            }

            if (Cache::get('admin_token_blacklist_' . $token)) {
                throw new \Exception('token已失效');
            }

            $admin = Admin::find($adminId);
            if (!$admin || $admin->status != 1) {
                throw new \Exception('管理员不存在或被禁用');
            }

            $request->adminId = $adminId;
            $request->admin = $admin;

        } catch (\Exception $e) {
            return json_error('认证失败：' . $e->getMessage(), 401);
        }

        return $next($request);
    }
}
