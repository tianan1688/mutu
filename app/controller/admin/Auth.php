<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Admin;
use app\model\OperationLog;
use Firebase\JWT\JWT;
use think\facade\Cache;
use think\facade\Config;

class Auth extends BaseController
{
    /**
     * 管理员登录
     */
    public function login()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');

        if (!$username || !$password) {
            return json_error('用户名和密码不能为空');
        }

        $admin = Admin::where('username', $username)->find();
        if (!$admin) {
            return json_error('用户不存在');
        }

        if (!password_verify($password, $admin->password)) {
            return json_error('密码错误');
        }

        if ($admin->status != 1) {
            return json_error('账号已被禁用');
        }

        // 生成JWT token
        $key = Config::get('jwt.key');
        $payload = [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'iat' => time(),
            'exp' => time() + (Config::get('jwt.expire') ?: 86400)
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        // 记录登录日志
        $admin->last_login_ip = get_client_ip();
        $admin->last_login_time = time();
        $admin->save();

        OperationLog::create([
            'admin_id' => $admin->id,
            'action' => '管理员登录',
            'ip' => get_client_ip(),
            'create_time' => time()
        ]);

        return json_success('登录成功', [
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'nickname' => $admin->nickname,
                'avatar' => $admin->avatar
            ]
        ]);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $token = $this->request->header('Admin-Token');
        if ($token) {
            // 加入黑名单，有效期到token过期时间
            Cache::set('admin_token_blacklist_' . $token, time(), 86400);
        }
        return json_success('退出成功');
    }
}