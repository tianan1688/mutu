<?php
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Filesystem;
use app\model\IpBlacklist;

class Setting extends BaseController
{
    /**
     * 基础信息配置
     */
    public function basic()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            foreach ($data as $key => $value) {
                set_config($key, $value);
            }
            return json_success('保存成功');
        }

        $config = [
            'site_name' => get_config('site_name', 'MuTu轻副业'),
            'site_logo' => get_config('site_logo', ''),
            'site_copyright' => get_config('site_copyright', ''),
            'site_icp' => get_config('site_icp', ''),
            'kefu_mobile' => get_config('kefu_mobile', ''),
            'kefu_qrcode' => get_config('kefu_qrcode', ''),
            'download_link_expire' => get_config('download_link_expire', 600),
            'download_limit_per_day' => get_config('download_limit_per_day', 10),
            'index_banners' => get_config('index_banners', '[]'),
        ];
        return json_success('ok', $config);
    }

    /**
     * 支付配置
     */
    public function payment()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            foreach ($data as $key => $value) {
                set_config($key, $value);
            }
            return json_success('保存成功');
        }

        $config = [
            'wx_appid' => get_config('wx_appid', ''),
            'wx_mchid' => get_config('wx_mchid', ''),
            'wx_key' => get_config('wx_key', ''),
            'wx_appsecret' => get_config('wx_appsecret', ''),
            'ali_partner' => get_config('ali_partner', ''),
            'ali_key' => get_config('ali_key', ''),
            'ali_seller_email' => get_config('ali_seller_email', ''),
        ];
        return json_success('ok', $config);
    }

   /**
 * 安全配置（合并了IP黑名单、水印、登录验证码）
 */
public function security()
{
    try {
        // GET请求：返回所有安全配置
        if ($this->request->isGet()) {
            // 获取IP黑名单列表
            $ipBlacklist = IpBlacklist::order('create_time', 'desc')->select();
            // 获取系统配置
            $config = [
                'login_captcha' => get_config('login_captcha', 1),
                'watermark_enable' => get_config('watermark_enable', 0),
                'watermark_text' => get_config('watermark_text', 'MuTu'),
                'ip_blacklist' => $ipBlacklist,
            ];
            return json_success('ok', $config);
        }

        // POST请求：处理不同配置类型
        $type = $this->request->post('type', 'config'); // 默认为config

        if ($type == 'ip') {
            // IP黑名单管理
            $action = $this->request->post('action', '');
            $ip = trim($this->request->post('ip', ''));
            $reason = trim($this->request->post('reason', ''));

            if ($action == 'add') {
                if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
                    return json_error('请输入有效的IP地址');
                }
                $exists = IpBlacklist::where('ip', $ip)->exists();
                if ($exists) {
                    return json_error('该IP已在黑名单中');
                }
                IpBlacklist::create([
                    'ip' => $ip,
                    'reason' => $reason,
                    'create_time' => time()
                ]);
                return json_success('添加成功');
            } elseif ($action == 'delete') {
                $id = (int)$this->request->post('id', 0);
                if ($id <= 0) return json_error('参数错误');
                IpBlacklist::destroy($id);
                return json_success('删除成功');
            } else {
                return json_error('未知操作');
            }
        } elseif ($type == 'config') {
            // 普通配置（登录验证码、水印等）
            $data = $this->request->post();
            foreach ($data as $key => $value) {
                set_config($key, $value);
            }
            return json_success('保存成功');
        } else {
            return json_error('类型错误');
        }
    } catch (\Exception $e) {
        if (env('APP_DEBUG')) {
            return json_error('服务器错误：' . $e->getMessage());
        }
        return json_error('服务器错误，请联系管理员');
    }
}

    /**
     * 上传文件（LOGO、客服二维码）
     */
    public function upload()
    {
        $file = $this->request->file('file');
        if (!$file) return json_error('请选择文件');

        try {
            $saveName = Filesystem::disk('public')->putFile('config', $file);
            $url = '/uploads/' . $saveName;
            return json_success('上传成功', ['url' => $url]);
        } catch (\Exception $e) {
            return json_error('上传失败：' . $e->getMessage());
        }
    }
}