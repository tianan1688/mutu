<?php
declare (strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use Firebase\JWT\JWT;
use think\facade\Cache;
use think\facade\Config;

class Auth extends BaseController
{
    /**
     * 登录
     */
    public function login()
    {
        $mobile = $this->request->post('mobile');
        $password = $this->request->post('password');
        $captcha = $this->request->post('captcha', '');

        if (!$mobile || !$password) {
            return json_error('手机号和密码不能为空');
        }

        // 验证码开关
        if (get_config('login_captcha', 1)) {
            if (!$captcha || !captcha_check($captcha)) {
                return json_error('验证码错误');
            }
        }

        $user = User::where('mobile', $mobile)->find();
        if (!$user) {
            return json_error('用户不存在');
        }
        if (!password_verify($password, $user->password)) {
            return json_error('密码错误');
        }
        if ($user->status != 1) {
            return json_error('账号已被禁用');
        }

        // 更新登录信息
        $user->last_login_ip = get_client_ip();
        $user->last_login_time = time();
        $user->save();

        // 生成JWT
        $key = Config::get('jwt.key');
        $payload = [
            'user_id' => $user->id,
            'mobile' => $user->mobile,
            'iat' => time(),
            'exp' => time() + (Config::get('jwt.expire') ?: 86400)
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        return json_success('登录成功', [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'mobile' => $user->mobile,
                'avatar' => $user->avatar,
                'level_id' => $user->level_id,
                'level_name' => $user->level_name,
                'expire_time' => $user->expire_time,
                'balance' => $user->balance,
                'points' => $user->points,
            ]
        ]);
    }

    /**
     * 注册
     */
    public function register()
    {
        $mobile = $this->request->post('mobile');
        $password = $this->request->post('password');
        $code = $this->request->post('code'); // 短信验证码
        $inviteCode = $this->request->post('invite_code', '');

        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return json_error('手机号格式不正确');
        }
        if (strlen($password) < 6) {
            return json_error('密码至少6位');
        }

        // 验证短信验证码（需对接短信服务，此处简化）
        $cachedCode = Cache::get('sms_' . $mobile);
        if (!$cachedCode || $cachedCode != $code) {
            return json_error('验证码错误');
        }

        // 检查手机号是否已注册
        if (User::where('mobile', $mobile)->find()) {
            return json_error('手机号已注册');
        }

        // 处理邀请人
        $pid = 0;
        if ($inviteCode) {
            $inviter = User::where('invite_code', $inviteCode)->find();
            if ($inviter) {
                $pid = $inviter->id;
            }
        }

        // 生成唯一邀请码
        $inviteCodeNew = $this->generateInviteCode();

        $user = User::create([
            'mobile' => $mobile,
            'password' => $password,
            'username' => '用户' . substr($mobile, -4),
            'invite_code' => $inviteCodeNew,
            'pid' => $pid,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        // 邀请奖励（如果启用）
        if ($pid > 0 && get_config('invite_reward_enable', 1)) {
            $pointsInviter = get_config('invite_points_inviter', 100);
            $pointsInvited = get_config('invite_points_invited', 50);

            // 记录邀请关系
            \app\model\InviteRecord::create([
                'invite_user_id' => $pid,
                'invited_user_id' => $user->id,
                'reward_points_inviter' => $pointsInviter,
                'reward_points_invited' => $pointsInvited,
                'create_time' => time(),
            ]);

            // 立即发放积分（或按需）
            if ($pointsInviter > 0) {
                $inviter->points += $pointsInviter;
                $inviter->save();
            }
            if ($pointsInvited > 0) {
                $user->points += $pointsInvited;
                $user->save();
            }
        }

        return json_success('注册成功');
    }

    /**
     * 生成唯一邀请码
     */
    private function generateInviteCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid() . mt_rand()), 0, 8));
        } while (User::where('invite_code', $code)->find());
        return $code;
    }

    /**
     * 发送短信验证码
     */
    public function sendSms()
    {
        $mobile = $this->request->post('mobile');
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return json_error('手机号格式不正确');
        }

        // 生成验证码
        $code = mt_rand(100000, 999999);
        // 存入缓存，5分钟有效
        Cache::set('sms_' . $mobile, $code, 300);

        // 实际发送短信（需对接阿里云等）
        // 这里仅返回code方便测试
        return json_success('发送成功', ['code' => $code]);
    }
    /**
 * 获取验证码图片
 */
public function captcha()
{
    return captcha();
}
}